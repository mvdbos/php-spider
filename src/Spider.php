<?php

namespace VDB\Spider;

use Exception;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\Downloader\DownloaderInterface;
use VDB\Spider\Event\DispatcherTrait;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Exception\MaxQueueSizeExceededException;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Http;

class Spider
{
    use DispatcherTrait;

    private DownloaderInterface $downloader;
    private QueueManagerInterface $queueManager;
    private DiscovererSet $discovererSet;
    private DiscoveredUri $seed;
    private string $spiderId;

    /**
     * @param string $seed the URI to start crawling
     * @param DiscovererSet|null $discovererSet
     * @param QueueManagerInterface|null $queueManager
     * @param DownloaderInterface|null $downloader
     * @param string|null $spiderId
     */
    public function __construct(
        string $seed,
        ?DiscovererSet $discovererSet = null,
        ?QueueManagerInterface $queueManager = null,
        ?DownloaderInterface  $downloader = null,
        ?string $spiderId = null
    ) {
        $this->setSeed($seed);
        $this->setSpiderId($spiderId);
        $this->setDiscovererSet($discovererSet ?: new DiscovererSet());
        $this->setQueueManager($queueManager ?: new InMemoryQueueManager());
        $this->setDownloader($downloader ?: new Downloader());

        // This makes the spider handle signals gracefully and allows us to do cleanup
        if (php_sapi_name() == 'cli') {
            declare(ticks=1);
            if (function_exists('pcntl_signal')) {
                pcntl_signal(SIGTERM, array($this, 'handleSignal'));
                pcntl_signal(SIGINT, array($this, 'handleSignal'));
                pcntl_signal(SIGHUP, array($this, 'handleSignal'));
                pcntl_signal(SIGQUIT, array($this, 'handleSignal'));
            }
        }
    }

    /**
     * @param string $uri
     */
    private function setSeed(string $uri): void
    {
        if (strlen($uri) == 0) {
            throw new InvalidArgumentException("Empty seed");
        }
        try {
            $seed = new Http($uri);
            $this->seed = new DiscoveredUri($seed->normalize(), 0);
        } catch (Exception $e) {
            throw new InvalidArgumentException("Invalid seed: " . $e->getMessage());
        }
    }

    private function setSpiderId(?string $spiderId): void
    {
        if (null !== $spiderId) {
            $this->spiderId = $spiderId;
        } else {
            $this->spiderId = md5($this->seed . microtime(true));
        }
    }

    /**
     * Starts crawling the URI provided on instantiation
     *
     * @return void
     */
    public function crawl(): void
    {
        $this->getQueueManager()->addUri($this->seed);
        $this->getDownloader()->getPersistenceHandler()->setSpiderId($this->spiderId);

        $this->dispatch(
            new GenericEvent($this, array('seed' => $this->seed)),
            SpiderEvents::SPIDER_CRAWL_PRE_CRAWL
        );

        $this->doCrawl();
    }

    /**
     * @return QueueManagerInterface
     */
    public function getQueueManager(): QueueManagerInterface
    {
        return $this->queueManager;
    }

    /**
     * param QueueManagerInterface $queueManager
     * @param QueueManagerInterface $queueManager
     */
    public function setQueueManager(QueueManagerInterface $queueManager): void
    {
        $this->queueManager = $queueManager;
    }

    /**
     * @return DownloaderInterface
     */
    public function getDownloader(): DownloaderInterface
    {
        return $this->downloader;
    }

    /**
     * @param DownloaderInterface $downloader
     * @return $this
     */
    public function setDownloader(DownloaderInterface $downloader): Spider
    {
        $this->downloader = $downloader;

        return $this;
    }

    /**
     * A shortcut for EventDispatcher::dispatch()
     *
     * @param GenericEvent $event
     * @param string $eventName
     */
    private function dispatch(GenericEvent $event, string $eventName): void
    {
        $this->getDispatcher()->dispatch($event, $eventName);
    }

    /**
     * Function that crawls each provided URI
     * It applies all processors and listeners set on the Spider
     *
     * This is a either depth first algorithm as explained here:
     *  https://en.wikipedia.org/wiki/Depth-first_search#Example
     * Note that because we don't do it recursive, but iteratively,
     * results will be in a different order from the example, because
     * we always take the right-most child first, whereas a recursive
     * variant would always take the left-most child first
     *
     * or
     *
     * a breadth first algorithm
     *
     * @return void
     *
     * @SuppressWarnings(PHPMD.IfStatementAssignment)
     */
    private function doCrawl(): void
    {
        while ($currentUri = $this->getQueueManager()->next()) {
            if ($this->getDownloader()->isDownLoadLimitExceeded()) {
                break;
            }

            if (!$resource = $this->getDownloader()->download($currentUri)) {
                continue;
            }

            $this->dispatch(
                new GenericEvent($this, array('uri' => $currentUri)),
                SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED
            );

            // Once the document is enqueued, apply the discoverers to look for more links to follow
            $discoveredUris = $this->getDiscovererSet()->discover($resource);

            foreach ($discoveredUris as $uri) {
                try {
                    $this->getQueueManager()->addUri($uri);
                } catch (MaxQueueSizeExceededException $e) {
                    // when the queue size is exceeded, we stop discovering
                    break;
                }
            }
        }
    }

    /**
     * @return DiscovererSet
     */
    public function getDiscovererSet(): DiscovererSet
    {
        return $this->discovererSet;
    }

    /**
     * param DiscovererSet $discovererSet
     * @param DiscovererSet $discovererSet
     */
    public function setDiscovererSet(DiscovererSet $discovererSet): void
    {
        $this->discovererSet = $discovererSet;
    }

    /**
     * @param $signal
     *
     * @codeCoverageIgnore
     */
    public function handleSignal($signal): void
    {
        switch ($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
            case SIGQUIT:
                $this->dispatch(
                    new GenericEvent($this, ['signal' => $signal]),
                    SpiderEvents::SPIDER_CRAWL_USER_STOPPED
                );
        }
    }

    /**
     * @return string
     */
    public function getSpiderId(): string
    {
        return $this->spiderId;
    }

    /**
     * Convenience method to set the download limit.
     *
     * @param int $limit Maximum number of resources to download
     * @return $this
     */
    public function setDownloadLimit(int $limit): self
    {
        $this->getDownloader()->setDownloadLimit($limit);
        return $this;
    }

    /**
     * Convenience method to set the persistence handler.
     * Note: This method requires the downloader to be an instance of Downloader (the concrete class).
     *
     * @param PersistenceHandlerInterface $handler
     * @return $this
     */
    public function setPersistenceHandler(PersistenceHandlerInterface $handler): self
    {
        $downloader = $this->getDownloader();
        if (!$downloader instanceof Downloader) {
            throw new RuntimeException(
                'setPersistenceHandler() requires a Downloader instance. ' .
                'The current downloader is ' . get_class($downloader)
            );
        }
        $downloader->setPersistenceHandler($handler);
        return $this;
    }

    /**
     * Convenience method to set the traversal algorithm.
     *
     * @param int $algorithm Either QueueManagerInterface::ALGORITHM_DEPTH_FIRST or ALGORITHM_BREADTH_FIRST
     * @return $this
     */
    public function setTraversalAlgorithm(int $algorithm): self
    {
        $this->getQueueManager()->setTraversalAlgorithm($algorithm);
        return $this;
    }

    /**
     * Convenience method to set the maximum crawl depth.
     *
     * @param int $depth Maximum depth to crawl
     * @return $this
     */
    public function setMaxDepth(int $depth): self
    {
        $this->getDiscovererSet()->maxDepth = $depth;
        return $this;
    }

    /**
     * Convenience method to set the maximum queue size.
     * Note: This method requires the queue manager to be an instance of InMemoryQueueManager (the concrete class).
     *
     * @param int $size Maximum number of URIs to queue
     * @return $this
     */
    public function setMaxQueueSize(int $size): self
    {
        $queueManager = $this->getQueueManager();
        if (!$queueManager instanceof InMemoryQueueManager) {
            throw new RuntimeException(
                'setMaxQueueSize() requires an InMemoryQueueManager instance. ' .
                'The current queue manager is ' . get_class($queueManager)
            );
        }
        $queueManager->maxQueueSize = $size;
        return $this;
    }

    /**
     * Convenience method to add a discoverer.
     *
     * @param DiscovererInterface $discoverer
     * @return $this
     */
    public function addDiscoverer(DiscovererInterface $discoverer): self
    {
        $this->getDiscovererSet()->set($discoverer);
        return $this;
    }

    /**
     * Convenience method to add a prefetch filter.
     *
     * @param PreFetchFilterInterface $filter
     * @return $this
     */
    public function addFilter(PreFetchFilterInterface $filter): self
    {
        $this->getDiscovererSet()->addFilter($filter);
        return $this;
    }

    /**
     * Convenience method to enable politeness policy.
     * Adds a listener that delays requests to the same domain.
     * Note: This method requires the downloader to be an instance of Downloader (the concrete class).
     *
     * @param int $delayInMilliseconds Delay in milliseconds between requests to the same domain
     * @return $this
     */
    public function enablePolitenessPolicy(int $delayInMilliseconds = 100): self
    {
        $downloader = $this->getDownloader();
        if (!$downloader instanceof Downloader) {
            throw new RuntimeException(
                'enablePolitenessPolicy() requires a Downloader instance. ' .
                'The current downloader is ' . get_class($downloader)
            );
        }
        
        $dispatcher = $downloader->getDispatcher();

        // Ensure only a single politeness listener is registered at any time.
        // If this method is called multiple times, replace the previous listener
        // instead of stacking delays.
        static $politenessListener = null;

        if ($politenessListener !== null) {
            $dispatcher->removeListener(
                SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
                array($politenessListener, 'onCrawlPreRequest')
            );
        }

        $politenessListener = new PolitenessPolicyListener($delayInMilliseconds);
        $dispatcher->addListener(
            SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
            array($politenessListener, 'onCrawlPreRequest')
        );
        return $this;
    }
}

<?php

namespace VDB\Spider;

use Exception;
use InvalidArgumentException;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Discoverer\DiscovererSetInterface;
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
    private DiscovererSetInterface $discovererSet;
    private DiscoveredUri $seed;
    private string $spiderId;

    /**
     * @param string $seed the URI to start crawling
     * @param DiscovererSetInterface|null $discovererSet
     * @param QueueManagerInterface|null $queueManager
     * @param DownloaderInterface|null $downloader
     * @param string|null $spiderId
     */
    public function __construct(
        string $seed,
        ?DiscovererSetInterface $discovererSet = null,
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
     * @return DiscovererSetInterface
     */
    public function getDiscovererSet(): DiscovererSetInterface
    {
        return $this->discovererSet;
    }

    /**
     * @param DiscovererSetInterface $discovererSet
     */
    public function setDiscovererSet(DiscovererSetInterface $discovererSet): void
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
     * Limits the total number of resources (web pages, documents, etc.) that will be
     * downloaded and persisted during the crawl. Once this limit is reached, the crawler
     * will stop downloading additional resources even if more URIs are queued.
     *
     * This is useful for:
     * - Testing and development (limiting crawl scope)
     * - Resource management (controlling memory/disk usage)
     * - Quick sampling of a website
     *
     * @param int $limit Maximum number of resources to download (must be positive)
     * @return $this Returns the Spider instance for method chaining
     */
    public function setDownloadLimit(int $limit): self
    {
        $this->getDownloader()->setDownloadLimit($limit);
        return $this;
    }

    /**
     * Convenience method to set the persistence handler.
     *
     * The persistence handler determines how downloaded resources are stored. By default,
     * resources are stored in memory, but you can provide custom handlers for:
     * - File system storage (e.g., \\VDB\\Spider\\PersistenceHandler\\FileSerializedResourcePersistenceHandler)
     * - Database storage
     * - Custom processing pipelines
     *
     * The handler must implement the PersistenceHandlerInterface and will receive each
     * successfully downloaded resource for storage/processing.
     *
     * @param PersistenceHandlerInterface $handler The persistence handler to use for storing resources
     * @return $this Returns the Spider instance for method chaining
     */
    public function setPersistenceHandler(PersistenceHandlerInterface $handler): self
    {
        $this->getDownloader()->setPersistenceHandler($handler);
        return $this;
    }

    /**
     * Convenience method to set the traversal algorithm.
     *
     * Controls the order in which discovered URIs are crawled:
     *
     * - ALGORITHM_DEPTH_FIRST (default): Follows links deeply before moving to siblings.
     *   Explores one branch completely before backtracking. Good for focused crawling.
     *
     * - ALGORITHM_BREADTH_FIRST: Crawls all links at one depth level before moving deeper.
     *   Explores all siblings before moving to children. Good for comprehensive site maps.
     *
     * @param int $algorithm Either QueueManagerInterface::ALGORITHM_DEPTH_FIRST or
     *                       QueueManagerInterface::ALGORITHM_BREADTH_FIRST
     * @return $this Returns the Spider instance for method chaining
     */
    public function setTraversalAlgorithm(int $algorithm): self
    {
        $this->getQueueManager()->setTraversalAlgorithm($algorithm);
        return $this;
    }

    /**
     * Convenience method to set the maximum crawl depth.
     *
     * Controls how many "hops" away from the seed URL the spider will follow links.
     * The seed URL is at depth 0, links found on the seed page are at depth 1, and so on.
     *
     * Examples:
     * - depth 0: Only the seed URL is crawled
     * - depth 1: Seed URL plus all directly linked pages
     * - depth 2: Seed URL, direct links, and their direct links
     *
     * Default is 3 if not set. Setting a lower depth reduces crawl scope and resource usage.
     *
     * @param int $depth Maximum depth to crawl (0 = seed only, 1 = seed + direct links, etc.)
     * @return $this Returns the Spider instance for method chaining
     */
    public function setMaxDepth(int $depth): self
    {
        $this->getDiscovererSet()->setMaxDepth($depth);
        return $this;
    }

    /**
     * Convenience method to set the maximum queue size.
     *
     * Limits the total number of URIs that can be queued for crawling. When this limit
     * is reached, the spider stops discovering and queueing new URIs, effectively capping
     * the total scope of the crawl.
     *
     * This is different from setDownloadLimit():
     * - setMaxQueueSize() limits URIs queued (may not all be downloaded)
     * - setDownloadLimit() limits resources actually downloaded
     *
     * Use this to control memory usage and prevent unbounded queue growth on large sites.
     * Set to 0 for unlimited queue size (default behavior).
     *
     * @param int $size Maximum number of URIs to queue (0 = unlimited)
     * @return $this Returns the Spider instance for method chaining
     */
    public function setMaxQueueSize(int $size): self
    {
        $this->getQueueManager()->setMaxQueueSize($size);
        return $this;
    }

    /**
     * Convenience method to add a discoverer.
     *
     * Discoverers extract URIs from downloaded resources. Without at least one discoverer,
     * the spider will only crawl the seed URL and stop.
     *
     * Common discoverers:
     * - \\VDB\\Spider\\Discoverer\\XPathExpressionDiscoverer: Extract links matching an XPath expression
     * - \\VDB\\Spider\\Discoverer\\CssSelectorDiscoverer: Extract links matching a CSS selector
     *
     * Multiple discoverers can be added, and they will all run on each resource.
     * Discovered URIs are combined, deduplicated, and filtered before being queued.
     *
     * @param DiscovererInterface $discoverer The discoverer to add to the discoverer set
     * @return $this Returns the Spider instance for method chaining
     */
    public function addDiscoverer(DiscovererInterface $discoverer): self
    {
        $this->getDiscovererSet()->addDiscoverer($discoverer);
        return $this;
    }

    /**
     * Convenience method to add a prefetch filter.
     *
     * Prefetch filters determine which discovered URIs should be excluded from crawling
     * BEFORE they are downloaded. This is more efficient than postfetch filters since
     * no HTTP request is made for filtered URIs.
     *
     * Common prefetch filters:
     * - \\VDB\\Spider\\Filter\\Prefetch\\RestrictToBaseUriFilter: Only crawl URIs under the seed domain/path
     * - \\VDB\\Spider\\Filter\\Prefetch\\AllowedHostsFilter: Restrict crawling to specific domains
     * - \\VDB\\Spider\\Filter\\Prefetch\\UriFilter: Filter URIs by regex pattern
     * - \\VDB\\Spider\\Filter\\Prefetch\\RobotsTxtDisallowFilter: Respect robots.txt rules
     *
     * Multiple filters can be added. A URI is excluded if ANY filter matches it.
     *
     * @param PreFetchFilterInterface $filter The filter to add to the discoverer set
     * @return $this Returns the Spider instance for method chaining
     */
    public function addFilter(PreFetchFilterInterface $filter): self
    {
        $this->getDiscovererSet()->addFilter($filter);
        return $this;
    }

    /**
     * Convenience method to enable politeness policy.
     *
     * Adds a delay between requests to the same domain to avoid overwhelming target servers
     * and to be a "good citizen" of the web. This is considered a best practice for web crawling.
     *
     * The delay is enforced per-domain:
     * - Requests to different domains can happen without delay
     * - Requests to the same domain wait for the specified delay
     *
     * Note: Calling this method multiple times will replace the previous politeness listener
     * with the new delay value, not stack additional delays.
     *
     * @param int $delayInMilliseconds Delay in milliseconds between requests to the same domain.
     *                                 Default is 100ms. Typical values range from 100-1000ms.
     * @return $this Returns the Spider instance for method chaining
     */
    public function enablePolitenessPolicy(int $delayInMilliseconds = 100): self
    {
        $dispatcher = $this->getDownloader()->getDispatcher();

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

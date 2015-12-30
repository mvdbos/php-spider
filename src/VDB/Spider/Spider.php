<?php
namespace VDB\Spider;

use Exception;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Exception\QueueException;
use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\PersistenceHandler\MemoryPersistenceHandler;
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\UriInterface;
use VDB\Uri\Uri;

/**
 *
 */
class Spider
{
    /** @var RequestHandlerInterface */
    private $requestHandler;

    /** @var PersistenceHandlerInterface */
    private $persistenceHandler;

    /** @var QueueManagerInterface */
    private $queueManager;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var DiscovererSet */
    private $discovererSet;

    /** @var PostFetchFilterInterface[] */
    private $postFetchFilters = array();

    /** @var DiscoveredUri The URI of the site to spider */
    private $seed = array();

    /** @var string the unique id of this spider instance */
    private $spiderId;

    /** @var int the maximum number of downloaded resources. 0 means no limit */
    public $downloadLimit = 0;

    /**
     * @param string $seed the URI to start crawling
     * @param string|null $spiderId
     */
    public function __construct($seed, $spiderId = null)
    {
        $this->setSeed($seed);
        if (null !== $spiderId) {
            $this->spiderId = $spiderId;
        } else {
            $this->spiderId = md5($seed . microtime(true));
        }

        // This makes the spider handle signals gracefully and allows us to do cleanup
        if (php_sapi_name() == 'cli') {
            declare(ticks = 1);
            if (function_exists('pcntl_signal')) {
                pcntl_signal(SIGTERM, array($this, 'handleSignal'));
                pcntl_signal(SIGINT, array($this, 'handleSignal'));
                pcntl_signal(SIGHUP, array($this, 'handleSignal'));
                pcntl_signal(SIGQUIT, array($this, 'handleSignal'));
            }
        }
    }

    /**
     * Starts crawling the URI provided on instantiation
     *
     * @return void
     */
    public function crawl()
    {
        $this->getQueueManager()->addUri($this->seed);
        $this->getPersistenceHandler()->setSpiderId($this->spiderId);

        $this->doCrawl();
    }

    /**
     * @param PostFetchFilterInterface $filter
     */
    public function addPostFetchFilter(PostFetchFilterInterface $filter)
    {
        $this->postFetchFilters[] = $filter;
    }

    /**
     * @param RequestHandlerInterface $requestHandler
     */
    public function setRequestHandler(RequestHandlerInterface $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandler()
    {
        if (!$this->requestHandler) {
            $this->requestHandler = new GuzzleRequestHandler();
        }

        return $this->requestHandler;
    }

    /**
     * param DiscovererSet $discovererSet
     */
    public function setDiscovererSet(DiscovererSet $discovererSet)
    {
        $this->discovererSet = $discovererSet;
    }

    /**
     * @return DiscovererSet
     */
    public function getDiscovererSet()
    {
        if (!$this->discovererSet) {
            $this->discovererSet = new DiscovererSet();
        }

        return $this->discovererSet;
    }

    /**
     * param QueueManagerInterface $queueManager
     */
    public function setQueueManager(QueueManagerInterface $queueManager)
    {
        $this->queueManager = $queueManager;
    }

    /**
     * @return QueueManagerInterface
     */
    public function getQueueManager()
    {
        if (!$this->queueManager) {
            $this->queueManager = new InMemoryQueueManager();
        }

        return $this->queueManager;
    }

    /**
     * @param PersistenceHandlerInterface $persistenceHandler
     */
    public function setPersistenceHandler(PersistenceHandlerInterface $persistenceHandler)
    {
        $this->persistenceHandler = $persistenceHandler;
    }

    /**
     * @return PersistenceHandlerInterface
     */
    public function getPersistenceHandler()
    {
        if (!$this->persistenceHandler) {
            $this->persistenceHandler = new MemoryPersistenceHandler();
        }

        return $this->persistenceHandler;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @return $this
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @return EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventDispatcher();
        }
        return $this->dispatcher;
    }

    public function handleSignal($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
            case SIGQUIT:
                $this->dispatch(SpiderEvents::SPIDER_CRAWL_USER_STOPPED);
        }
    }

    /**
     * @param Resource $resource
     * @return bool
     */
    private function matchesPostfetchFilter(Resource $resource)
    {
        foreach ($this->postFetchFilters as $filter) {
            if ($filter->match($resource)) {
                $this->dispatch(
                    SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
                    new GenericEvent($this, array('uri' => $resource->getUri()))
                );
                return true;
            }
        }
        return false;
    }

    private function isDownLoadLimitExceeded()
    {
        return $this->downloadLimit !== 0 && $this->getPersistenceHandler()->count() >= $this->downloadLimit;
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
     */
    private function doCrawl()
    {
        while ($currentUri = $this->getQueueManager()->next()) {
            if ($this->isDownLoadLimitExceeded()) {
                break;
            }

            // Fetch the document
            if (!$resource = $this->fetchResource($currentUri)) {
                continue;
            }

            $this->getPersistenceHandler()->persist($resource);

            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED,
                new GenericEvent($this, array('uri' => $currentUri))
            );

            // Once the document is enqueued, apply the discoverers to look for more links to follow
            $discoveredUris = $this->getDiscovererSet()->discover($resource);

            foreach ($discoveredUris as $uri) {
                try {
                    $this->getQueueManager()->addUri($uri);
                } catch (QueueException $e) {
                    // when the queue size is exceeded, we stop discovering
                    break;
                }
            }
        }
    }

    /**
     * @param UriInterface $uri
     * @return Resource|false
     */
    protected function fetchResource(DiscoveredUri $uri)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, new GenericEvent($this, array('uri' => $uri)));

        try {
            $resource = $this->getRequestHandler()->request($uri);

            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST, new GenericEvent($this, array('uri' => $uri))); // necessary until we have 'finally'

            if ($this->matchesPostfetchFilter($resource)) {
                return false;
            }

            return $resource;
        } catch (\Exception $e) {
            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST,
                new GenericEvent($this, array('uri' => $uri, 'message' => $e->getMessage()))
            );

            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST, new GenericEvent($this, array('uri' => $uri))); // necessary until we have 'finally'

            return false;
        }
    }

    /**
     * A shortcut for EventDispatcher::dispatch()
     *
     * @param string $eventName
     * @param null|Event $event
     */
    private function dispatch($eventName, Event $event = null)
    {
        $this->getDispatcher()->dispatch($eventName, $event);
    }

    /**
     * @param string $uri
     */
    private function setSeed($uri)
    {
        $this->seed = new DiscoveredUri(new Uri($uri));
        $this->seed->setDepthFound(0);
    }
}

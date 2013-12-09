<?php
namespace VDB\Spider;

use Exception;
use Guzzle\Http\Message\Response;
use Guzzle\Http\Url;
use Guzzle\Parser\ParserRegistry;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\Discoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Exception\QueueException;
use VDB\Spider\Filter\PostFetchFilter;
use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\PersistenceHandler\MemoryPersistenceHandler;
use VDB\Spider\PersistenceHandler\PersistenceHandler;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\RequestHandler\RequestHandler;
use VDB\Spider\StatsHandler;
use VDB\Spider\Uri\FilterableUri;
use VDB\Uri\Uri;
use VDB\Uri\Http;
use VDB\Uri\UriInterface;

/**
 *
 */
class Spider
{
    const ALGORITHM_DEPTH_FIRST = 0;
    const ALGORITHM_BREADTH_FIRST = 1;

    /** @var RequestHandler */
    private $requestHandler;

    /** @var StatsHandler */
    private $statsHandler;

    /** @var PersistenceHandler */
    private $persistenceHandler;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var Discoverer[] */
    private $discoverers = array();

    /** @var PreFetchFilter[] */
    private $preFetchFilters = array();

    /** @var PostFetchFilter[] */
    private $postFetchFilters = array();

    /** @var Resource[] all Resources enqueued for processing */
    protected $processQueue = array();

    /** @var Uri The URI of the site to spider */
    private $seed = array();

    /** @var int The maximum depth for the crawl */
    private $maxDepth = 3;

    /** @var int The maximum size of the process queue for this spider. 0 means infinite */
    private $maxQueueSize = 0;

    /** @var int the amount of times a Resource was enqueued */
    private $currentQueueSize = 0;

    /** @var array the list of already visited URIs with the depth they were discovered on as value */
    private $alreadySeenUris = array();

    /** @var Uri[] the list of URIs to process */
    private $traversalQueue = array();

    /** @var int The traversal algorithm to use. Choose from the class constants */
    private $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST;

    /** @var string the unique id of this spider instance */
    private $spiderId;

    /**
     * @param string $seed the URI to start crawling
     * @param string $spiderId
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
        if(php_sapi_name() == 'cli'){
            declare(ticks = 1);
            pcntl_signal(SIGTERM, array($this, 'handleSignal'));
            pcntl_signal(SIGINT, array($this, 'handleSignal'));
            pcntl_signal(SIGHUP, array($this, 'handleSignal'));
            pcntl_signal(SIGQUIT, array($this, 'handleSignal'));
        }
    }

    /**
     * Starts crawling the URI provided on instantiation
     *
     * @return array
     */
    public function crawl()
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_START);
        $this->getStatsHandler()->setSpiderId($this->spiderId);
        $this->getPersistenceHandler()->setSpiderId($this->spiderId);

        try {
            $this->doCrawl();
        } catch (QueueException $e) {
            // do nothing, we got here because we reached max queue size.
        }

        $this->dispatch(SpiderEvents::SPIDER_CRAWL_END);
    }

    /**
     * @param Discoverer $discoverer
     */
    public function addDiscoverer(Discoverer $discoverer)
    {
        array_push($this->discoverers, $discoverer);
    }

    /**
     * @param PreFetchFilter $filter
     */
    public function addPreFetchFilter(PreFetchFilter $filter)
    {
        $this->preFetchFilters[] = $filter;
    }

    /**
     * @param PostFetchFilter $filter
     */
    public function addPostFetchFilter(PostFetchFilter $filter)
    {
        $this->postFetchFilters[] = $filter;
    }

    /**
     * @param int $maxDepth
     */
    public function setMaxDepth($maxDepth)
    {
        $this->maxDepth = $maxDepth;
    }

    /**
     * @return int
     */
    public function getMaxDepth()
    {
        return $this->maxDepth;
    }

    /**
     * @param int $traversalAlgorithm Choose from the class constants
     */
    public function setTraversalAlgorithm($traversalAlgorithm)
    {
        $this->traversalAlgorithm = $traversalAlgorithm;
    }

    /**
     * @return int
     */
    public function getTraversalAlgorithm()
    {
        return $this->traversalAlgorithm;
    }

    /**
     * @param int $maxQueueSize
     */
    public function setMaxQueueSize($maxQueueSize)
    {
        $this->maxQueueSize = $maxQueueSize;
    }

    /**
     * @return int
     */
    public function getMaxQueueSize()
    {
        return $this->maxQueueSize;
    }

    /**
     * @param RequestHandler $requestHandler
     */
    public function setRequestHandler(RequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * @return RequestHandler
     */
    public function getRequestHandler()
    {
        if (!$this->requestHandler) {
            $this->requestHandler = new GuzzleRequestHandler();
        }

        return $this->requestHandler;
    }

    /**
     * @param PersistenceHandler $persistenceHandler
     */
    public function setPersistenceHandler($persistenceHandler)
    {
        $this->persistenceHandler = $persistenceHandler;
    }

    /**
     * @return PersistenceHandler
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

    /**
     * @param \VDB\Spider\StatsHandler $statsHandler
     */
    public function setStatsHandler($statsHandler)
    {
        $this->statsHandler = $statsHandler;
    }

    /**
     * @return \VDB\Spider\StatsHandler
     */
    public function getStatsHandler()
    {
        if (!$this->statsHandler) {
            $this->statsHandler = new StatsHandler();
        }

        return $this->statsHandler;
    }

    public function handleSignal($signal)
    {
        switch ($signal) {
            case SIGTERM:
            case SIGKILL:
            case SIGINT:
            case SIGQUIT:
                echo "\n\nCAUGHT SIGNAL. TERMINATING\n\n";
                echo $this->statsHandler->toString();
                exit();
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
                return true;
            }
        }
        return false;
    }

    /**
     * @param Uri $uri
     * @return bool
     */
    private function matchesPrefetchFilter(FilterableUri $uri)
    {
        foreach ($this->preFetchFilters as $filter) {
            if ($filter->match($uri)) {
                return true;
            }
        }
        return false;
    }

    private function getNextUriFromQueue()
    {
        if ($this->traversalAlgorithm === static::ALGORITHM_DEPTH_FIRST) {
            return array_pop($this->traversalQueue);
        } elseif ($this->traversalAlgorithm === static::ALGORITHM_BREADTH_FIRST) {
            return array_shift($this->traversalQueue);
        } else {
            throw new \LogicException('No search algorithm set');
        }
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
        while (count($this->traversalQueue)) {
            /** @var $currentUri Uri */
            $currentUri = $this->getNextUriFromQueue();

            // Fetch the document
            if (!$resource = $this->fetchResource($currentUri)) {
                continue;
            }

            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
                new GenericEvent($this, array('document' => $resource))
            );

            if ($this->matchesPostfetchFilter($resource)) {
                $this->getStatsHandler()->addToFiltered($resource);
                continue;
            }

            // The document was not filtered, so we add it to the processing queue
            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_PRE_ENQUEUE,
                new GenericEvent($this, array('document' => $resource))
            );

            $this->addToProcessQueue($resource);

            $nextLevel = $this->alreadySeenUris[$currentUri->toString()] + 1;
            if ($nextLevel > $this->maxDepth) {
                continue;
            }

            // Once the document is enqueued, apply the discoverers to look for more links to follow
            $discoveredUris = $this->executeDiscoverers($resource);

            foreach ($discoveredUris as $uri) {
                // normalize the URI
                $uri->normalize();

                // Decorate the link to make it filterable
                $uri = new FilterableUri($uri);

                // Always skip nodes we already visited
                if (array_key_exists($uri->toString(), $this->alreadySeenUris)) {
                    continue;
                }

                $this->dispatch(
                    SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH,
                    new GenericEvent($this, array('uri' => $uri))
                );

                if ($this->matchesPrefetchFilter($uri)) {
                    $this->getStatsHandler()->addToFiltered($uri);
                } else {
                    // The URI was not matched by any filter, mark as visited and add to queue
                    array_push($this->traversalQueue, $uri);
                }
                $this->alreadySeenUris[$uri->toString()] = $nextLevel;
            }
        }
    }

    /**
     * Add a Resource to the processing queue
     *
     * @param Resource $resource
     * @return void
     */
    protected function addToProcessQueue(Resource $resource)
    {
        if ($this->maxQueueSize != 0 && $this->currentQueueSize >= $this->maxQueueSize) {
            $resource->setFiltered(true, 'Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
            $this->getStatsHandler()->addToFiltered($resource);
            throw new QueueException('Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
        }

        $this->currentQueueSize++;
        $this->getPersistenceHandler()->persist($resource);
        $this->getStatsHandler()->addToQueued($resource->getUri());
    }

    /**
     * @param Resource $resource
     * @return Uri[]
     */
    protected function executeDiscoverers(Resource $resource)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_DISCOVER);

        $discoveredUris = array();

        foreach ($this->discoverers as $discoverer) {
            $discoveredUris = array_merge($discoveredUris, $discoverer->discover($this, $resource));
        }

        $this->deduplicateUris($discoveredUris);

        $this->dispatch(
            SpiderEvents::SPIDER_CRAWL_POST_DISCOVER,
            new GenericEvent($this, array('uris' => $discoveredUris))
        );

        return $discoveredUris;
    }

    /**
     * @param Uri $uri
     * @return bool|Resource
     */
    protected function fetchResource(UriInterface $uri)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, new GenericEvent($this, array('uri' => $uri)));
        try {
            $resource = $this->getRequestHandler()->request($uri);
            $resource->depthFound = $this->alreadySeenUris[$uri->toString()];
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST); // necessary until we have 'finally'
            return $resource;
        } catch (\Exception $e) {
            $this->getStatsHandler()->addToFailed($uri->toString(), $e->getMessage());

            $this->dispatch(SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST);
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST); // necessary until we have 'finally'

            return false;
        }
    }

    /**
     * A shortcut for EventDispatcher::dispatch()
     *
     * @param string $eventName
     * @param Event $event
     */
    private function dispatch($eventName, Event $event = null)
    {
        $this->getDispatcher()->dispatch($eventName, $event);
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function deduplicateUris(array &$discoveredUris)
    {
        // make sure there are no duplicates in the list
        $tmp = array();
        /** @var Uri $uri */
        foreach ($discoveredUris as $k => $uri) {
            $tmp[$k] = $uri->toString();
        }

        // Find duplicates in temporary array
        $tmp = array_unique($tmp);

        // Remove the duplicates from original array
        foreach ($discoveredUris as $k => $uri) {
            if (!array_key_exists($k, $tmp)) {
                unset($discoveredUris[$k]);
            }
        }
    }

    /**
     * @param string $uri
     */
    private function setSeed($uri)
    {
        $this->seed = new Http($uri);
        array_push($this->traversalQueue, $this->seed);
        $this->alreadySeenUris[$this->seed->normalize()->toString()] = 0;
    }
}

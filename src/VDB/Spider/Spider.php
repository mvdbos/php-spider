<?php
namespace VDB\Spider;

use Exception;
use Guzzle\Http\Url;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Exception\QueueException;
use VDB\Spider\Filter\PostFetchFilter;
use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\RequestHandler\RequestHandler;
use VDB\Spider\RequestHandler\RequestHandlerBrowserKitClient;
use VDB\Spider\URI\FilterableURI;
use VDB\URI\HttpURI;
use VDB\URI\URI;

/**
 *
 */
class Spider
{
    const ALGORITHM_DEPTH_FIRST = 0;
    const ALGORITHM_BREADTH_FIRST = 1;

    /** @var RequestHandler */
    private $requestHandler;

    /** @var Discoverer[] */
    private $discoverers = array();

    /** @var URI The URI of the site to spider */
    private $seed = array();

    /** @var array all filtered URIs, with the reason as value */
    private $filtered = array();

    /** @var array all failed URIs, with exception information */
    private $failed = array();

    /** @var array all processed URIs */
    private $processed = array();

    /** @var PreFetchFilter[] */
    private $preFetchFilter = array();

    /** @var PostFetchFilter[] */
    private $postFetchFilter = array();

    /** @var array all URIs enqueued for processing */
    protected $processQueue = array();

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var int The maximum depth for the crawl */
    private $maxDepth = 3;

    /** @var int The maximum size of the process queue for this spider. 0 means infinite */
    private $maxQueueSize = 0;

    /** @var int the amount of times a Resource was enqueued */
    private $currentQueueSize = 0;

    /** @var array the list of already visited URIs with the depth they were discovered on as value */
    private $visitedURIs = array();

    /** @var array the list of URIs to process */
    private $traversalQueue = array();

    /** @var int The traversal algorithm to use. Choose from the class constants */
    private $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST;

    /**
     * @param string $seed the URI to start crawling
     */
    public function __construct($seed)
    {
        $this->setSeed($seed);
    }

    /**
     * Starts crawling the URI provided on instantiation
     *
     * @return array
     */
    public function crawl()
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_START);

        try {
            $this->doCrawl();
        } catch (QueueException $e) {
            // do nothing, we got here because we reached max queue size.
        }

        $this->dispatch(SpiderEvents::SPIDER_CRAWL_END);

        $enqueued = array();
        foreach ($this->processQueue as $resource) {
            /** @var $resource Resource */
            $enqueued[] = $resource;
        }

        return array(
            'spiderId' => hash('md5', $this->seed->toString() . microtime()),
            'filtered' => $this->filtered,
            'failed' => $this->failed,
            'queued' => $enqueued
        );
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
        $this->preFetchFilter[] = $filter;
    }

    /**
     * @param PostFetchFilter $filter
     */
    public function addPostFetchFilter(PostFetchFilter $filter)
    {
        $this->postFetchFilter[] = $filter;
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
     * @param \VDB\Spider\RequestHandler\RequestHandler $requestHandler
     */
    public function setRequestHandler(RequestHandler $requestHandler)
    {
        $this->requestHandler = $requestHandler;
    }

    /**
     * @return \VDB\Spider\RequestHandler\RequestHandler|RequestHandlerBrowserKitClient
     */
    public function getRequestHandler()
    {
        if (!$this->requestHandler) {
            $this->requestHandler = new RequestHandlerBrowserKitClient();
        }
        return $this->requestHandler;
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
     * @param Resource $resource
     * @return bool
     */
    private function matchesPostfetchFilter(Resource $resource)
    {
        foreach ($this->postFetchFilter as $filter) {
            if ($filter->match($resource)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param URI $uri
     * @return bool
     */
    private function matchesPrefetchFilter(FilterableURI $uri)
    {
        foreach ($this->preFetchFilter as $filter) {
            if ($filter->match($uri)) {
                return true;
            }
        }
        return false;
    }

    private function getNextURIFromQueue()
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
     * @param URI $currentURI
     * @return void
     */
    private function doCrawl()
    {
        while (count($this->traversalQueue)) {
            /** @var $currentURI URI */
            $currentURI = $this->getNextURIFromQueue();

            // Fetch the document
            if (!$resource = $this->fetchResource($currentURI)) {
                continue;
            }

            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
                new GenericEvent($this, array('document' => $resource))
            );

            if ($this->matchesPostfetchFilter($resource)) {
                $this->addToFiltered($resource);
                continue;
            }

            // The document was not filtered, so we add it to the processing queue
            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_PRE_ENQUEUE,
                new GenericEvent($this, array('document' => $resource))
            );

            $this->addToProcessQueue($resource);

            $nextLevel = $this->visitedURIs[$currentURI->toString()] + 1;
            if ($nextLevel > $this->maxDepth) {
                continue;
            }

            // Once the document is enqueued, apply the discoverers to look for more links to follow
            $discoveredURIs = $this->executeDiscoverers($resource);

            foreach ($discoveredURIs as $uri) {
                // normalize the URI
                $uri->normalize();

                // Decorate the link to make it filterable
                $uri = new FilterableURI($uri);

                // Always skip nodes we already visited
                if (array_key_exists($uri->toString(), $this->visitedURIs)) {
                    $uri->setFiltered(true, 'Already visited');
                    $this->addToFiltered($uri);
                    continue;
                }

                $this->dispatch(
                    SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH,
                    new GenericEvent($this, array('uri' => $uri))
                );

                if ($this->matchesPrefetchFilter($uri)) {
                    $this->addToFiltered($uri);
                } else {
                    // The URI was not matched by any filter, mark as visited and add to queue
                    $this->visitedURIs[$uri->toString()] = $nextLevel;
                    array_push($this->traversalQueue, $uri);
                }
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
            $this->addToFiltered($resource);
            throw new QueueException('Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
        }

        $this->currentQueueSize++;
        $this->processQueue[] = $resource;
    }

    /**
     * @param Resource $resource
     * @return URI[]
     */
    protected function executeDiscoverers(Resource $resource)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_DISCOVER);

        $discoveredGenericURIs = array();

        foreach ($this->discoverers as $discoverer) {
            $discoveredGenericURIs = array_merge($discoveredGenericURIs, $discoverer->discover($this, $resource));
        }

        $this->deduplicateGenericURIs($discoveredGenericURIs);

        $this->dispatch(
            SpiderEvents::SPIDER_CRAWL_POST_DISCOVER,
            new GenericEvent($this, array('uris' => $discoveredGenericURIs))
        );

        return $discoveredGenericURIs;
    }

    /**
     * @param URI $uri
     * @return bool|Resource
     */
    protected function fetchResource(URI $uri)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, new GenericEvent($this, array('uri' => $uri)));
        try {
            $resource = $this->getRequestHandler()->request($uri);
            $resource->depthFound = $this->visitedURIs[$uri->toString()];
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST); // necessary until we have 'finally'
            return $resource;
        } catch (\Exception $e) {
            $this->addToFailed($uri->toString(), $e->getMessage());

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
     * @param Filterable $item
     */
    private function addToFiltered(Filterable $item)
    {
        // we might encounter the URI twice, don't overwrite the original reason for filtering
        if (!array_key_exists($item->getIdentifier(), $this->filtered)) {
            $this->filtered[$item->getIdentifier()] = $item->getFilterReason();
        }
    }

    /**
     * @param string $uri
     * @param string $reason
     */
    public function addToFailed($uri, $reason)
    {
        $this->failed[$uri] = $reason;
    }

    /**
     * @param string $uri
     */
    private function addToProcessed($uri)
    {
        $this->processed[] = $uri;
    }

    /**
     * @param URI[] $discoveredURIs
     */
    private function deduplicateGenericURIs(array &$discoveredURIs)
    {
        // make sure there are no duplicates in the list
        $tmp = array();
        /** @var URI $uri */
        foreach ($discoveredURIs as $k => $uri) {
            $tmp[$k] = $uri->toString();
        }

        // Find duplicates in temporary array
        $tmp = array_unique($tmp);

        // Remove the duplicates from original array
        foreach ($discoveredURIs as $k => $uri) {
            if (!array_key_exists($k, $tmp)) {
                unset($discoveredURIs[$k]);
            }
        }
    }

    /**
     * @param string $uri
     */
    private function setSeed($uri)
    {
        $this->seed = new HttpURI($uri);

        array_push($this->traversalQueue, $this->seed);
        $this->visitedURIs[$this->seed->normalize()->toString()] = 0;
    }
}

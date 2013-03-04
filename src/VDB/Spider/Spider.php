<?php
namespace VDB\Spider;

use VDB\Spider\Processor;
use VDB\URI\HttpURI;
use VDB\URI\URI;
use VDB\Spider\Exception\QueueException;
use VDB\Spider\Discoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Filter\PostFetchFilter;
use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\RequestHandler\RequestHandler;
use VDB\Spider\RequestHandler\RequestHandlerBrowserKitClient;

use Guzzle\Http\Url;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

use Pimple;

use Exception;

/**
 *
 */
class Spider
{
    const ALGORITHM_DEPTH_FIRST = 0;
    const ALGORITHM_BREADTH_FIRST = 1;


    /** @var RequestHandler */
    private $requestHandler;

    /** @var Processor[] */
    private $processors = array();

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

    /** @var Pimple The DIC */
    private $container;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var int The maximum depth for the crawl */
    private $maxDepth = 3;

    /** @var int the current crawl depth */
    private $currentDepth = array();

    /** @var int The maximum size of the process queue for this spider. 0 means infinite */
    private $maxQueueSize = 0;

    /** @var int the amount of times a Document was enqueued */
    private $currentQueueSize = 0;

    /** @var array the list of already visited URIs with the depth they were discovered on as value */
    private $visitedURIs = array();

    /** @var array the list of URIs to process */
    private $traversalQueue = array();

    /**
     * @var int
     * The algorithm to use. Choose from the class constants
     * Defaults to ALGORITHM_DEPTH_FIRST
     */
    private $traversalAlgorithm = self::ALGORITHM_DEPTH_FIRST;

    /**
     * @param \Pimple $container
     */
    public function __construct(Pimple $container)
    {
        $this->container = $container;
    }

    /**
     * Starts crawling the URI provided on instantiation
     *
     * @param $uri
     * @return array
     */
    public function crawl($uri)
    {
        $this->setSeed($uri);
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_START);

        try {
            array_push($this->traversalQueue, $this->seed);
            $this->visitedURIs[$this->seed->recompose()] = 0;
            $this->doCrawl();
        } catch (QueueException $e) {
            // do nothing, this is the only way to break the recursion
        }

        $this->dispatch(SpiderEvents::SPIDER_CRAWL_END);

        $processed = array();
        foreach ($this->processQueue as $document) {
            /** @var $document Document */
            $processed[] = "(d=".$document->depthFound.")" . $document->getUri()->recompose();
        }

        return array(
            'spiderId'      => hash('md5', $uri.microtime()),
            'filtered'      => $this->filtered,
            'failed'        => $this->failed,
            'queued'        => $processed
        );
    }

    /**
     * Start processing all collected Documents
     *
     * @return array an array of all processed URIs
     */
    public function process()
    {
        $this->dispatch(SpiderEvents::SPIDER_PROCESS_START);
        foreach ($this->processQueue as $document) {
            /** @var $document Document */
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_PROCESS_DOCUMENT);
            foreach ($this->processors as $processor) {
                $processor->execute($document);
            }
            $this->addToProcessed($document->getUri()->recompose());
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_PROCESS_DOCUMENT);
        }
        $this->dispatch(SpiderEvents::SPIDER_PROCESS_END);

        return $this->processed;
    }

    /**
     * @param Processor $processor
     */
    public function addProcessor(Processor $processor)
    {
        array_push($this->processors, $processor);
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
            $this->requestHandler = new \VDB\Spider\RequestHandler\RequestHandlerBrowserKitClient();
        }

        return $this->requestHandler;
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $EventDispatcher
     * @return Spider
     */
    public function setDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->dispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getDispatcher()
    {
        if (!$this->dispatcher) {
            $this->dispatcher = new EventDispatcher();
        }

        return $this->dispatcher;
    }

    /**
     * @param Document $document
     * @return bool
     */
    private function matchesPostfetchFilter(Document $document)
    {
        foreach ($this->postFetchFilter as $filter) {
            if ($filter->match($document)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param URI $uri
     * @return bool
     */
    private function matchesPrefetchFilter(URI $uri)
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
     */
    private function doCrawl()
    {
        while (count($this->traversalQueue)) {
            /** @var $currentURI URI  */
            $currentURI = $this->getNextURIFromQueue();

            // Fetch the document
            if (!$document = $this->fetchDocument($currentURI)) {
                continue;
            }

            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
                new GenericEvent($this, array('document' => $document))
            );

            if ($this->matchesPostfetchFilter($document)) {
                $this->addToFiltered($document);
                continue;
            }

            // The document was not filtered, so we add it to the processing queue
            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_PRE_ENQUEUE,
                new GenericEvent($this, array('document' => $document))
            );

            $this->addToProcessQueue($document);

            $nextLevel = $this->visitedURIs[$currentURI->recompose()] + 1;
            if ($nextLevel > $this->maxDepth) {
                continue;
            }

            // Once the document is enqueued, apply the discoverers to look for more links to follow
            $discoveredURIs = $this->executeDiscoverers($document);

            foreach ($discoveredURIs as $uri) {
                // Decorate the link to make it filterable
                $uri = new FilterableURI($uri);

                // Always skip nodes we already visited
                if (array_key_exists($uri->recompose(), $this->visitedURIs)) {
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
                    $this->visitedURIs[$uri->recompose()] = $nextLevel;
                    array_push($this->traversalQueue, $uri);
                }
            }
        }
    }

    /**
     * Add a Document to the processing queue
     *
     * @param Document $document
     */
    protected function addToProcessQueue(Document $document)
    {
        if ($this->maxQueueSize != 0 && $this->currentQueueSize >= $this->maxQueueSize) {
            $this->addToFailed(
                $document->getUri()->recompose(),
                'Maximum Queue Size of ' . $this->maxQueueSize . ' reached'
            );
            throw new QueueException('Maximum Queue Size of ' . $this->maxQueueSize . ' reached');
        }

        $this->currentQueueSize++;

        $this->processQueue[] = $document;
    }

    /**
     * @param Document $document
     * @return URI[]
     */
    protected function executeDiscoverers(Document $document)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_DISCOVER);

        $discoveredGenericURIs = array();

        foreach ($this->discoverers as $discoverer) {
            $discoveredGenericURIs = array_merge($discoveredGenericURIs, $discoverer->discover($this, $document));
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
     * @return Document|false
     */
    protected function fetchDocument(URI $uri)
    {
        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, new GenericEvent($this, array('uri' => $uri)));
        try {
            $document = $this->requestHandler->request($uri);
            $document->depthFound = $this->visitedURIs[$uri->recompose()];
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST); // necessary until we have 'finally'
            return $document;
        } catch (\Exception $e) {
            $this->addToFailed($uri->recompose(), $e->getMessage());

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
     * @param Filterable $GenericURI
     * @param string $reason
     */
    private function addToFiltered(Filterable $uri)
    {
        // we might encounter the URI twice, don't overwrite the original reason for filtering
        if (!array_key_exists($uri->getIdentifier(), $this->filtered)) {
            $this->filtered[$uri->getIdentifier()] = $uri->getFilterReason();
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
     * @param string $reason
     */
    private function addToProcessed($uri)
    {
        $this->processed[] = $uri;
    }

    /**
     * @param URI[] $discoveredGenericURIs
     */
    private function deduplicateGenericURIs(array &$discoveredGenericURIs)
    {
        // make sure there are no duplicates in the list
        $tmp = array();
        /** @var URI $uri */
        foreach ($discoveredGenericURIs as $k => $uri) {
            $tmp[$k] = $uri->recompose();
        }

        // Find duplicates in temporary array
        $tmp = array_unique($tmp);

        // Remove the duplicates from original array
        foreach ($discoveredGenericURIs as $k => $uri) {
            if (!array_key_exists($k, $tmp)) {
                unset($discoveredGenericURIs[$k]);
            }
        }
    }

    /**
     * @param string $uri
     */
    private function setSeed($uri)
    {
        $this->seed = new HttpURI($uri);
    }
}

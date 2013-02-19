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

    /** @var int The maximum size of the process queue for this spider */
    private $maxQueueSize = 0;

    /** @var int the amount of times a Document was enqueued */
    private $currentQueueSize = 0;

    /** @var int the current crawl depth */
    private $currentDepth = 0;

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
            $this->doCrawl($this->seed);
        } catch (QueueException $e) {
            // do nothing, this is the only way to break the recursion
        }

        $this->dispatch(SpiderEvents::SPIDER_CRAWL_END);

        $processed = array();
        foreach ($this->processQueue as $document) {
            /** @var $document Document */
            $processed[] = $document->getUri()->recompose();
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

    /**
     * Recursive function that crawls each provided URI
     * It applies all processors and listeners set on the Spider
     *
     * @param URI $currentURI
     */
    private function doCrawl(URI $currentURI)
    {
        // Fetch the document
        if (!$document = $this->fetchDocument($currentURI)) {
            return;
        }

        $this->dispatch(
            SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
            new GenericEvent($this, array('document' => $document))
        );

        if ($this->matchesPostfetchFilter($document)) {
            $this->addToFiltered($document);
            return;
        }

        // The document was not filtered, so we add it to the processing queue
        $this->dispatch(
            SpiderEvents::SPIDER_CRAWL_PRE_ENQUEUE,
            new GenericEvent($this, array('document' => $document))
        );

        $this->addToProcessQueue($document);

        // only crawl more links if we are not yet at maxDepth
        if ($this->currentDepth < $this->maxDepth) {

            // Once the document is enqueued, apply the discoverers to look for more links to follow
            $discoveredURIs = $this->executeDiscoverers($document);

            foreach ($discoveredURIs as $uri) {
                // Decorate the link to make it filterable
                $uri = new FilterableURI($uri);

                $this->dispatch(
                    SpiderEvents::SPIDER_CRAWL_FILTER_PREFETCH,
                    new GenericEvent($this, array('uri' => $uri))
                );

                if ($this->matchesPrefetchFilter($uri)) {
                    $this->addToFiltered($uri);
                } else {
                    // The URI was not matched by any filter, recurse
                    $this->currentDepth++;
                    $this->doCrawl($uri);
                }
            }
        }
        $this->currentDepth--;
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

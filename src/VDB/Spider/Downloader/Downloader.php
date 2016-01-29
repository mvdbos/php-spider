<?php

namespace VDB\Spider\Downloader;

use VDB\Spider\Downloader\DownloaderInterface;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\PersistenceHandler\MemoryPersistenceHandler;
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class Downloader implements DownloaderInterface
{
    /** @var EventDispatcherInterface */
    private $dispatcher;

    /** @var PersistenceHandlerInterface */
    private $persistenceHandler;

    /** @var RequestHandlerInterface */
    private $requestHandler;

    /** @var int the maximum number of downloaded resources. 0 means no limit */
    private $downloadLimit = 0;

    /** @var PostFetchFilterInterface[] */
    private $postFetchFilters = array();

    /**
     * @param int Maximum number of resources to download
     * @return $this
     */
    public function setDownloadLimit($downloadLimit)
    {
        $this->downloadLimit = $downloadLimit;
        return $this;
    }

    /**
     * @return int Maximum number of resources to download
     */
    public function getDownloadLimit()
    {
        return $this->downloadLimit;
    }

    /**
     * @param PostFetchFilterInterface $filter
     */
    public function addPostFetchFilter(PostFetchFilterInterface $filter)
    {
        $this->postFetchFilters[] = $filter;
    }

    /**
     * @param DiscoveredUri $uri
     * @return false|Resource
     */
    public function download(DiscoveredUri $uri)
    {
        // Fetch the document
        if (!$resource = $this->fetchResource($uri)) {
            return false;
        }

        if ($this->matchesPostfetchFilter($resource)) {
            return false;
        }

        $this->getPersistenceHandler()->persist($resource);

        return $resource;
    }

    public function isDownLoadLimitExceeded()
    {
        return $this->getDownloadLimit() !== 0 && $this->getPersistenceHandler()->count() >= $this->getDownloadLimit();
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
     * @param DiscoveredUri $uri
     * @return Resource|false
     */
    protected function fetchResource(DiscoveredUri $uri)
    {
        $resource = false;

        $this->dispatch(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, new GenericEvent($this, array('uri' => $uri)));

        try {
            $resource = $this->getRequestHandler()->request($uri);
        } catch (\Exception $e) {
            $this->dispatch(
                SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST,
                new GenericEvent($this, array('uri' => $uri, 'message' => $e->getMessage()))
            );
        } finally {
            $this->dispatch(SpiderEvents::SPIDER_CRAWL_POST_REQUEST, new GenericEvent($this, array('uri' => $uri)));
        }

        return $resource;
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
}

<?php

namespace VDB\Spider\Downloader;

use Exception;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\DispatcherTrait;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\PersistenceHandler\MemoryPersistenceHandler;
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class Downloader implements DownloaderInterface
{
    use DispatcherTrait;

    /** @var PersistenceHandlerInterface */
    private $persistenceHandler;

    /** @var RequestHandlerInterface */
    private $requestHandler;

    /** @var int the maximum number of downloaded resources. 0 means no limit */
    private $downloadLimit = 0;

    /** @var PostFetchFilterInterface[] */
    private $postFetchFilters = array();

    /**
     * Downloader constructor.
     * @param PersistenceHandlerInterface|null $persistenceHandler
     * @param RequestHandlerInterface|null $requestHandler
     * @param PostFetchFilterInterface[] $postFetchFilters
     * @param int $downloadLimit
     */
    public function __construct(
        PersistenceHandlerInterface $persistenceHandler = null,
        RequestHandlerInterface $requestHandler = null,
        array $postFetchFilters = array(),
        int $downloadLimit = 0
    ) {
        $this->setPersistenceHandler($persistenceHandler ?: new MemoryPersistenceHandler());
        $this->setRequestHandler($requestHandler ?: new GuzzleRequestHandler());
        foreach ($postFetchFilters as $filter) {
            $this->addPostFetchFilter($filter);
        }
        $this->setDownloadLimit($downloadLimit);
    }

    /**
     * @param int $downloadLimit Maximum number of resources to download
     * @return $this
     */
    public function setDownloadLimit(int $downloadLimit): DownloaderInterface
    {
        $this->downloadLimit = $downloadLimit;
        return $this;
    }

    /**
     * @return int Maximum number of resources to download
     */
    public function getDownloadLimit(): int
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
        $resource = $this->fetchResource($uri);

        if (!$resource) {
            return false;
        }

        if ($this->matchesPostfetchFilter($resource)) {
            return false;
        }

        $this->getPersistenceHandler()->persist($resource);

        return $resource;
    }

    public function isDownLoadLimitExceeded(): bool
    {
        return $this->getDownloadLimit() !== 0 && $this->getPersistenceHandler()->count() >= $this->getDownloadLimit();
    }

    /**
     * A shortcut for EventDispatcher::dispatch()
     *
     * @param GenericEvent $event
     * @param string $eventName
     */
    private function dispatch(GenericEvent $event, string $eventName)
    {
        $this->getDispatcher()->dispatch($event, $eventName);
    }

    /**
     * @param DiscoveredUri $uri
     * @return Resource|false
     */
    protected function fetchResource(DiscoveredUri $uri)
    {
        $resource = false;

        $this->dispatch(new GenericEvent($this, array('uri' => $uri)), SpiderEvents::SPIDER_CRAWL_PRE_REQUEST);

        try {
            $resource = $this->getRequestHandler()->request($uri);
        } catch (Exception $e) {
            $this->dispatch(
                new GenericEvent($this, array('uri' => $uri, 'message' => $e->getMessage())),
                SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST
            );
        } finally {
            $this->dispatch(
                new GenericEvent($this, array('uri' => $uri)),
                SpiderEvents::SPIDER_CRAWL_POST_REQUEST
            );
        }

        return $resource;
    }

    /**
     * @param Resource $resource
     * @return bool
     */
    private function matchesPostfetchFilter(Resource $resource): bool
    {
        foreach ($this->postFetchFilters as $filter) {
            if ($filter->match($resource)) {
                $this->dispatch(
                    new GenericEvent($this, array('uri' => $resource->getUri())),
                    SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH
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
    public function getPersistenceHandler(): PersistenceHandlerInterface
    {
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
    public function getRequestHandler(): RequestHandlerInterface
    {
        return $this->requestHandler;
    }
}

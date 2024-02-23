<?php

namespace VDB\Spider\Downloader;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\DispatcherTrait;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Logging\LoggingTrait;
use VDB\Spider\PersistenceHandler\MemoryPersistenceHandler;
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class Downloader implements DownloaderInterface
{
    use DispatcherTrait, LoggingTrait;

    /** @var PersistenceHandlerInterface */
    private PersistenceHandlerInterface $persistenceHandler;

    /** @var RequestHandlerInterface */
    private RequestHandlerInterface $requestHandler;

    /** @var int the maximum number of downloaded resources. 0 means no limit */
    private int $downloadLimit = 0;

    /** @var PostFetchFilterInterface[] */
    private array $postFetchFilters = array();

    public function __construct(
        ?PersistenceHandlerInterface $persistenceHandler = null,
        ?RequestHandlerInterface $requestHandler = null,
        array $postFetchFilters = array(),
        int $downloadLimit = 0,
        LoggerInterface $logger = null,
    ) {
        if ($logger !== null) {
            $this->setLogger($logger);
        }
        $this->setPersistenceHandler($persistenceHandler ?: new MemoryPersistenceHandler());
        $this->setRequestHandler($requestHandler ?: new GuzzleRequestHandler());
        foreach ($postFetchFilters as $filter) {
            $this->addPostFetchFilter($filter);
        }
        $this->setDownloadLimit($downloadLimit);
    }

    public function setDownloadLimit(int $downloadLimit): DownloaderInterface
    {
        $this->downloadLimit = $downloadLimit;
        return $this;
    }

    public function addPostFetchFilter(PostFetchFilterInterface $filter): void
    {
        $this->postFetchFilters[] = $filter;
    }

    public function download(DiscoveredUri $uri): Resource|false
    {
        $resource = $this->fetchResource($uri);

        if (!$resource) {
            return false;
        }

        if ($this->matchesPostfetchFilter($resource)) {
            return false;
        }

        if (!$this->getPersistenceHandler()->persist($resource)) {
            return false;
        }

        return $resource;
    }

    public function isDownLoadLimitExceeded(): bool
    {
        return $this->downloadLimit !== 0 && $this->getPersistenceHandler()->count() >= $this->downloadLimit;
    }


    /**
     * @param DiscoveredUri $uri
     * @return Resource|false
     */
    protected function fetchResource(DiscoveredUri $uri): Resource|false
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
    public function setPersistenceHandler(PersistenceHandlerInterface $persistenceHandler): void
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
    public function setRequestHandler(RequestHandlerInterface $requestHandler): void
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

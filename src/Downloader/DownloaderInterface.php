<?php

namespace VDB\Spider\Downloader;

use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

interface DownloaderInterface
{
    /**
     * @return bool Returns true if the downloadlimit is exceeded
     */
    public function isDownLoadLimitExceeded(): bool;

    /**
     * @param DiscoveredUri $uri
     * @return false|Resource
     */
    public function download(DiscoveredUri $uri);

    /**
     * @param int Maximum number of resources to download
     * @return $this
     */
    public function setDownloadLimit(int $downloadLimit): DownloaderInterface;

    /**
     * @return int Maximum number of resources to download
     */
    public function getDownloadLimit(): int;

    /**
     * @return RequestHandlerInterface
     */
    public function getRequestHandler(): RequestHandlerInterface;

    /**
     * @param RequestHandlerInterface $requestHandler
     */
    public function setRequestHandler(RequestHandlerInterface $requestHandler);

    /**
     * @return PersistenceHandlerInterface
     */
    public function getPersistenceHandler(): PersistenceHandlerInterface;
}

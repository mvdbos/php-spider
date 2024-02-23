<?php

namespace VDB\Spider\Downloader;

use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

interface DownloaderInterface
{
    public function download(DiscoveredUri $uri): Resource|false;
    public function isDownLoadLimitExceeded(): bool;
    public function setDownloadLimit(int $downloadLimit): DownloaderInterface;
    public function getRequestHandler(): RequestHandlerInterface;
    public function setRequestHandler(RequestHandlerInterface $requestHandler);
    public function getPersistenceHandler(): PersistenceHandlerInterface;
    public function setPersistenceHandler(PersistenceHandlerInterface $persistenceHandler);
}

<?php

namespace VDB\Spider\RequestHandler;

use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Resource;

interface RequestHandlerInterface
{
    /**
     * @param DiscoveredUri $uri
     * @return Resource
     */
    public function request(DiscoveredUri $uri);
}

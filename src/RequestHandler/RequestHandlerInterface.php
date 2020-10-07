<?php

namespace VDB\Spider\RequestHandler;

use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

interface RequestHandlerInterface
{
    /**
     * @param DiscoveredUri $uri
     * @return Resource
     */
    public function request(DiscoveredUri $uri);
}

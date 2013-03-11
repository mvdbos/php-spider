<?php

namespace VDB\Spider\RequestHandler;

use VDB\Uri\UriInterface;

interface RequestHandler
{
    /**
     * @param URI $uri
     * @return Resource
     */
    public function request(URI $uri);
}

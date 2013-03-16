<?php

namespace VDB\Spider\RequestHandler;

use VDB\Uri\UriInterface;

interface RequestHandler
{
    /**
     * @param UriInterface $uri
     * @return Resource
     */
    public function request(UriInterface $uri);
}

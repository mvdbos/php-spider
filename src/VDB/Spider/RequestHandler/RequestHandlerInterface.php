<?php

namespace VDB\Spider\RequestHandler;

use VDB\Uri\UriInterface;

interface RequestHandlerInterface
{
    /**
     * @param UriInterface $uri
     * @return Resource
     */
    public function request(UriInterface $uri);
}

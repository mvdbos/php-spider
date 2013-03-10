<?php

namespace VDB\Spider\RequestHandler;

use VDB\URI\URI;

interface RequestHandler
{
    /**
     * @param URI $uri
     * @return Resource
     */
    public function request(URI $uri);
}

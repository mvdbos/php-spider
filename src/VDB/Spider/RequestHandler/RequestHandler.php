<?php

namespace VDB\Spider\RequestHandler;

use Symfony\Component\DomCrawler\Link;
use VDB\URI\URI;
use VDB\Spider\Resource;

/**
 *
 */
interface RequestHandler
{
    /**
     * @param \Symfony\Component\DomCrawler\Link $Link
     * @return Resource
     */
    public function request(URI $uri);
}

<?php

namespace VDB\Spider\RequestHandler;

use Symfony\Component\DomCrawler\Link;
use VDB\URI\URI;
use VDB\Spider\Document;

/**
 *
 */
interface RequestHandler
{
    /**
     * @param \Symfony\Component\DomCrawler\Link $Link
     * @return Document
     */
    public function request(URI $uri);
}

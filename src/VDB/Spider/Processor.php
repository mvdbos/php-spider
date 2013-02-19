<?php

namespace VDB\Spider;

use Symfony\Component\DomCrawler\Crawler;

/**
 * @author matthijs
 */

interface Processor
{
    /**
     * @param string $uri
     * @return boolean
     */
    public function execute($uri, Crawler $crawler);
}
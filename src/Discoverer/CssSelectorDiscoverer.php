<?php

namespace VDB\Spider\Discoverer;

use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class CssSelectorDiscoverer extends CrawlerDiscoverer
{
    protected function getFilteredCrawler(Resource $resource): Crawler
    {
        return $resource->getCrawler()->filter($this->selector);
    }
}

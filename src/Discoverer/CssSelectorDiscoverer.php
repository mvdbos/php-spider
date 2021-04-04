<?php

namespace VDB\Spider\Discoverer;

use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class CssSelectorDiscoverer extends CrawlerDiscoverer
{
    protected function getFilteredCrawler(Resource $resource): Crawler
    {
        return $resource->getCrawler()->filter($this->selector);
    }
}

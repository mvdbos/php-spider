<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class CssSelectorDiscoverer extends CrawlerDiscoverer
{
    protected function getFilteredCrawler(Resource $resource)
    {
        return $resource->getCrawler()->filter($this->selector);
    }
}

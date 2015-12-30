<?php
namespace VDB\Spider\Discoverer;

use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Discoverer\CrawlerDiscoverer;
use VDB\Spider\Resource;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\UriInterface;

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

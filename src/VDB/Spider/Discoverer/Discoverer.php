<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
interface Discoverer
{
    /**
     * @param Spider $spider
     * @param Resource $document
     * @return UriInterface[]
     */
    public function discover(Spider $spider, Resource $document);
}

<?php

namespace VDB\Spider;

use Symfony\Component\DomCrawler\Link;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
interface Discoverer
{
    /**
     * @param Resource $document
     * @return Link[] An array of Link instances
     */
    public function discover(Spider $spider, Resource $document);
}

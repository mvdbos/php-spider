<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\URI\URI;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
interface Discoverer
{
    /**
     * @param Spider $spider
     * @param Resource $document
     * @return URI[]
     */
    public function discover(Spider $spider, Resource $document);
}

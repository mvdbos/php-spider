<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
interface DiscovererInterface
{
    /**
     * @param Resource $resource
     * @return UriInterface[]
     */
    public function discover(Resource $resource);

    /**
     * @param DiscovererSet $discovererSet
     */
    public function setDiscovererSet(DiscovererSet $discovererSet);

    /**
     * @return string The name of this discoverer
     */
    public function getName();
}

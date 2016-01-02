<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
abstract class Discoverer implements DiscovererInterface
{
    /** @var DiscovererSet */
    protected $discovererSet;

    /**
     * @param DiscovererSet $discovererSet
     */
    public function setDiscovererSet(DiscovererSet $discovererSet)
    {
        $this->discovererSet = $discovererSet;
    }

    public function getName()
    {
        return get_class($this);
    }

    /**
     * @param Resource $resource
     * @return DiscoveredUri[]
     */
    abstract public function discover(Resource $resource);
}

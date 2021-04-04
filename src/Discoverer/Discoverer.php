<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
abstract class Discoverer implements DiscovererInterface
{
    public function getName(): string
    {
        return get_class($this);
    }

    /**
     * @param Resource $resource
     * @return DiscoveredUri[]
     */
    abstract public function discover(Resource $resource): array;
}

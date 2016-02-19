<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Uri\DiscoveredUri;

class AlreadySeenUris
{
    private $alreadySeenUris;

    public function __construct()
    {
        $this->alreadySeenUris = new \ArrayObject();
    }

    public function wasSeen(DiscoveredUri $uri)
    {
        if ($this->alreadySeenUris->offsetExists($uri->normalize()->toString())) {
            return true;
        }
        return false;
    }

    /**
     * @param DiscoveredUri $uri
     *
     * Mark an Uri as already seen.
     *
     * If it already exists, it is not overwritten, since we want to keep the
     * first depth it was found at.
     */
    public function markSeen(DiscoveredUri $uri)
    {
        $uriString = $uri->normalize()->toString();
        $this->alreadySeenUris->offsetSet($uriString, $uri->getDepthFound());
    }
}

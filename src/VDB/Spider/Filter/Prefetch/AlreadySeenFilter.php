<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author matthijs
 */
class AlreadySeenFilter implements PreFetchFilterInterface
{
    /**
     * @var \ArrayObject the list of already visited URIs with the depth they were discovered on as value
     */
    private $alreadySeenUris;

    public function __construct(\ArrayObject $alreadySeenUris)
    {
        $this->alreadySeenUris = $alreadySeenUris;
    }

    public function match(DiscoveredUri $uri)
    {
        if ($this->wasSeen($uri)) {
            return true;
        }

        $this->markSeen($uri);
        return false;
    }

    private function wasSeen(DiscoveredUri $uri)
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

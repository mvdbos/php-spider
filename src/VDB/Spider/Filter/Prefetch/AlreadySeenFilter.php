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
     * @var array the list of already visited URIs with the depth they were discovered on as value
     */
    private $alreadySeenUris = array();

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
        if (array_key_exists($uri->normalize()->toString(), $this->alreadySeenUris)) {
            return true;
        }
    }

    /**
     * @param DiscoveredUri $uri
     *
     * Mark an Uri as already seen.
     *
     * If it already exists, it is not overwritten, since we want to keep the
     * first depth it was found at.
     */
    private function markSeen(DiscoveredUri $uri)
    {
        $uriString = $uri->normalize()->toString();
        $this->alreadySeenUris[$uriString] = $uri->getDepthFound();
    }
}

<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Discoverer\AlreadySeenUris;

/**
 * @author matthijs
 */
class AlreadySeenFilter implements PreFetchFilterInterface
{
    /**
     * @var AlreadySeenUris the list of already visited URIs with the depth they were discovered on as value
     */
    private $alreadySeenUris;

    public function __construct(AlreadySeenUris $alreadySeenUris)
    {
        $this->alreadySeenUris = $alreadySeenUris;
    }

    public function match(DiscoveredUri $uri)
    {
        if ($this->alreadySeenUris->wasSeen($uri)) {
            return true;
        }

        $this->alreadySeenUris->markSeen($uri);
        return false;
    }
}

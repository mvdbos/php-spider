<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class DiscoveredUris
{
    private $discoveredUris = [];

    private $depthFound = 0;

    /**
     * @param DiscoveredUri[] The list of discovered Uris
     * @param int The depth at which the Uris were found
     */
    public function __construct(array $uris, $depthFound)
    {
        $this->depthFound = $depthFound;

        foreach ($uris as $uri) {
            $this->add($uri);
        }
    }

    private function add(DiscoveredUri $uri)
    {
        $uri->normalize();
        $uri->setDepthFound($this->depthFound);
        $this->discoveredUris[] = $uri;
    }

    public function merge(DiscoveredUris $uris)
    {
        $this->discoveredUris = array_merge($this->toArray(), $uris->toArray());
    }

    public function toArray()
    {
        return $this->discoveredUris;
    }

    public function filter(array $filters)
    {
        foreach ($this->discoveredUris as $k => $uri) {
            foreach ($filters as $filter) {
                if ($filter->match($uri)) {
                    unset($this->discoveredUris[$k]);
                }
            }
        }
    }
}

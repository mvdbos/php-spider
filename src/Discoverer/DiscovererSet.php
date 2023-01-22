<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\UriInterface;

class DiscovererSet
{
    /**
     * @var Discoverer[]
     */
    private array $discoverers = array();

    /** @var PreFetchFilterInterface[] */
    private array $filters = array();

    /**
     * @var int maximum crawl depth
     */
    public int $maxDepth = 3;

    /**
     * @var array the list of already visited URIs with the depth they were discovered on as value
     */
    private array $alreadySeenUris = array();

    public function __construct(array $discoverers = array())
    {
        foreach ($discoverers as $discoverer) {
            $this->set($discoverer);
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
        if (!array_key_exists($uriString, $this->alreadySeenUris)) {
            $this->alreadySeenUris[$uriString] = $uri->getDepthFound();
        }
    }

    /**
     * @param DiscoveredUri $uri
     * @return bool Returns true if this URI was found at max depth
     */
    private function isAtMaxDepth(DiscoveredUri $uri): bool
    {
        return $uri->getDepthFound() === $this->maxDepth;
    }

    /**
     * @param Resource $resource
     * @return DiscoveredUri[]
     */
    public function discover(Resource $resource): array
    {
        $this->markSeen($resource->getUri());

        if ($this->isAtMaxDepth($resource->getUri())) {
            return [];
        }

        $discoveredUris = [];

        foreach ($this->discoverers as $discoverer) {
            $discoveredUris = array_merge($discoveredUris, $discoverer->discover($resource));
        }

        $this->normalize($discoveredUris);
        $this->removeDuplicates($discoveredUris);
        $this->filterAlreadySeen($discoveredUris);
        $this->filter($discoveredUris);

        // reset the indexes of the discovered URIs after filtering
        $discoveredUris = array_values($discoveredUris);

        foreach ($discoveredUris as $uri) {
            $this->markSeen($uri);
        }

        return $discoveredUris;
    }

    /**
     * Sets a discoverer.
     *
     * @param discovererInterface $discoverer The discoverer instance
     */
    public function set(DiscovererInterface $discoverer)
    {
        $this->discoverers[$discoverer->getName()] = $discoverer;
    }

    public function addFilter(PreFetchFilterInterface $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function normalize(array &$discoveredUris)
    {
        /** @var DiscoveredUri[] $discoveredUris */
        foreach ($discoveredUris as $k => $uri) {
            $discoveredUris[$k] = $uri->normalize();
        }
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function filterAlreadySeen(array &$discoveredUris)
    {
        foreach ($discoveredUris as $k => $uri) {
            if (array_key_exists($uri->toString(), $this->alreadySeenUris)) {
                unset($discoveredUris[$k]);
            }
        }
    }

    /**
     * Filter out any URI that matches any of the filters
     * @param UriInterface[] $discoveredUris
     */
    private function filter(array &$discoveredUris)
    {
        foreach ($discoveredUris as $k => $uri) {
            foreach ($this->filters as $filter) {
                if ($filter->match($uri)) {
                    unset($discoveredUris[$k]);
                }
            }
        }
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function removeDuplicates(array &$discoveredUris)
    {
        // make sure there are no duplicates in the list
        $tmp = array();
        foreach ($discoveredUris as $k => $uri) {
            $tmp[$k] = $uri->toString();
        }

        // Find duplicates in temporary array
        $tmp = array_unique($tmp);

        // Remove the duplicates from original array
        foreach ($discoveredUris as $k => $uri) {
            if (!array_key_exists($k, $tmp)) {
                unset($discoveredUris[$k]);
            }
        }
    }
}

<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class DiscovererSet implements DiscovererSetInterface
{
    /**
     * @var array<string, DiscovererInterface>
     */
    private array $discoverers = array();

    /** @var PreFetchFilterInterface[] */
    private array $filters = array();

    /**
     * @var int maximum crawl depth
     * @deprecated Use setMaxDepth() and getMaxDepth() methods instead. Direct property access will be removed in a future version.
     */
    public int $maxDepth = 3;

    /**
     * @var array the list of already visited URIs with the depth they were discovered on as value
     */
    private array $alreadySeenUris = array();

    public function __construct(array $discoverers = array())
    {
        foreach ($discoverers as $discoverer) {
            $this->addDiscoverer($discoverer);
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
    private function markSeen(DiscoveredUri $uri): void
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
        return $uri->getDepthFound() === $this->getMaxDepth();
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
     * Adds a discoverer to the set.
     *
     * @param DiscovererInterface $discoverer The discoverer instance
     * @return $this
     */
    public function addDiscoverer(DiscovererInterface $discoverer): self
    {
        $this->discoverers[$discoverer->getName()] = $discoverer;
        return $this;
    }

    /**
     * Adds a discoverer to the set.
     * Alias for addDiscoverer() for backward compatibility.
     *
     * @param DiscovererInterface $discoverer The discoverer instance
     * @return $this
     * @deprecated Use addDiscoverer() instead
     */
    public function set(DiscovererInterface $discoverer): self
    {
        trigger_error(
            'DiscovererSet::set() is deprecated and will be removed in a future major version. Use addDiscoverer() instead.',
            E_USER_DEPRECATED
        );
        return $this->addDiscoverer($discoverer);
    }

    /**
     * Adds a prefetch filter.
     *
     * @param PreFetchFilterInterface $filter
     * @return $this
     */
    public function addFilter(PreFetchFilterInterface $filter): self
    {
        $this->filters[] = $filter;
        return $this;
    }

    /**
     * Gets the maximum crawl depth.
     *
     * @return int
     * @suppress PhanDeprecatedProperty
     */
    public function getMaxDepth(): int
    {
        return $this->maxDepth;
    }

    /**
     * Sets the maximum crawl depth.
     *
     * @param int $depth Maximum crawl depth
     * @return $this
     * @suppress PhanDeprecatedProperty
     */
    public function setMaxDepth(int $depth): self
    {
        $this->maxDepth = $depth;
        return $this;
    }

    /**
     * Normalizes all discovered URIs.
     *
     * @param DiscoveredUri[] $discoveredUris
     */
    private function normalize(array &$discoveredUris): void
    {
        foreach ($discoveredUris as $k => $uri) {
            $discoveredUris[$k] = $uri->normalize();
        }
    }

    /**
     * Filters out URIs that have already been seen.
     *
     * @param DiscoveredUri[] $discoveredUris
     */
    private function filterAlreadySeen(array &$discoveredUris): void
    {
        foreach ($discoveredUris as $k => $uri) {
            if (array_key_exists($uri->toString(), $this->alreadySeenUris)) {
                unset($discoveredUris[$k]);
            }
        }
    }

    /**
     * Applies prefetch filters to discovered URIs.
     * Filters out any URI that matches any of the filters.
     *
     * @param DiscoveredUri[] $discoveredUris
     */
    private function filter(array &$discoveredUris): void
    {
        foreach ($discoveredUris as $k => $uri) {
            foreach ($this->filters as $filter) {
                if ($filter->match($uri)) {
                    unset($discoveredUris[$k]);
                    break; // No need to check other filters once matched
                }
            }
        }
    }

    /**
     * Removes duplicate URIs from the list.
     *
     * @param DiscoveredUri[] $discoveredUris
     */
    private function removeDuplicates(array &$discoveredUris): void
    {
        $seenUris = [];

        foreach ($discoveredUris as $k => $uri) {
            $uriString = $uri->toString();
            if (isset($seenUris[$uriString])) {
                unset($discoveredUris[$k]);
            } else {
                $seenUris[$uriString] = true;
            }
        }
    }
}

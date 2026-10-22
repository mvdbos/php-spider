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
            foreach ($discoverer->discover($resource) as $uri) {
                $discoveredUris[] = $uri;
            }
        }

        $uniqueUris = $this->normalizeAndDeduplicate($discoveredUris);
        $this->filter($uniqueUris);

        // the URIs that survived filtering are keyed by their normalized string,
        // so we can mark them as seen without recomputing the normalized string
        foreach ($uniqueUris as $uriString => $uri) {
            $this->alreadySeenUris[$uriString] = $uri->getDepthFound();
        }

        // reset the indexes of the discovered URIs after filtering
        return array_values($uniqueUris);
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
     * Normalizes all discovered URIs and removes duplicates: both duplicates within the
     * given list and URIs that have already been seen before.
     *
     * Normalizing, stringifying and de-duplicating happens in a single pass, keyed by the
     * normalized URI string, so each URI is only normalized and stringified once.
     *
     * @param DiscoveredUri[] $discoveredUris
     * @return array<string, DiscoveredUri> the unique URIs, keyed by their normalized string
     */
    private function normalizeAndDeduplicate(array $discoveredUris): array
    {
        $uniqueUris = [];

        foreach ($discoveredUris as $uri) {
            $uri->normalize();
            $uriString = $uri->toString();
            if (array_key_exists($uriString, $this->alreadySeenUris) || array_key_exists($uriString, $uniqueUris)) {
                continue;
            }
            $uniqueUris[$uriString] = $uri;
        }

        return $uniqueUris;
    }

    /**
     * Applies prefetch filters to discovered URIs.
     * Filters out any URI that matches any of the filters.
     *
     * @param array<string, DiscoveredUri> $discoveredUris
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
}

<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

interface DiscovererSetInterface
{
    /**
     * Discovers URIs from a resource.
     *
     * @param Resource $resource
     * @return DiscoveredUri[]
     */
    public function discover(Resource $resource): array;

    /**
     * Sets a discoverer.
     *
     * @param DiscovererInterface $discoverer The discoverer instance
     * @return $this
     */
    public function set(DiscovererInterface $discoverer): self;

    /**
     * Adds a prefetch filter.
     *
     * @param PreFetchFilterInterface $filter
     * @return $this
     */
    public function addFilter(PreFetchFilterInterface $filter): self;

    /**
     * Gets the maximum crawl depth.
     *
     * @return int
     */
    public function getMaxDepth(): int;

    /**
     * Sets the maximum crawl depth.
     *
     * @param int $depth Maximum crawl depth
     * @return $this
     */
    public function setMaxDepth(int $depth): self;
}

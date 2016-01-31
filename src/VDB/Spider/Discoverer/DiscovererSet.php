<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Filter\PreFetch\AlreadySeenFilter;
use VDB\Spider\Uri\DiscoveredUri;

class DiscovererSet
{
    /**
     * @var Discoverer[]
     */
    private $discoverers = array();

    /** @var Filter[] */
    private $filters = array();

    /**
     * @var int maximum crawl depth
     */
    public $maxDepth = 3;


    public function __construct(array $discoverers = array())
    {
        $this->addFilter(new AlreadySeenFilter());

        foreach ($discoverers as $alias => $discoverer) {
            $this->set($discoverer, is_int($alias) ? null : $alias);
        }
    }

    /**
     * @return bool Returns true if this URI was found at max depth
     */
    private function isAtMaxDepth(DiscoveredUri $uri)
    {
        return $uri->getDepthFound() === $this->maxDepth;
    }

    /**
     * @param Resource $resource
     * @return UriInterface[]
     */
    public function discover(Resource $resource)
    {
        //$this->markSeen($resource->getUri());

        if ($this->isAtMaxDepth($resource->getUri())) {
            return [];
        }

        $foundDepth = $resource->getUri()->getDepthFound() + 1;

        $discoveredUris = new DiscoveredUris([], $foundDepth);

        foreach ($this->discoverers as $discoverer) {
            $uris = $discoverer->discover($resource);
            $discoveredUris->merge(new DiscoveredUris($uris, $foundDepth));
        }

        $discoveredUris->filter($this->filters);

        return $discoveredUris->toArray();
    }

    /**
     * Sets a discoverer.
     *
     * @param discovererInterface $discoverer The discoverer instance
     * @param string|null         $alias  An alias
     */
    public function set(DiscovererInterface $discoverer, $alias = null)
    {
        $this->discoverers[$discoverer->getName()] = $discoverer;
        if (null !== $alias) {
            $this->discoverers[$alias] = $discoverer;
        }
    }

    /**
     * @param PreFetchFilterInterface $filter
     */
    public function addFilter(PreFetchFilterInterface $filter)
    {
        $this->filters[] = $filter;
    }
}

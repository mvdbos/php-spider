<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

class DiscovererSet implements \IteratorAggregate
{

    /**
     * @var Discoverer[]
     */
    private $discoverers = array();

    /** @var Filter[] */
    private $filters = array();

    public function __construct(array $discoverers = array())
    {
        foreach ($discoverers as $alias => $discoverer) {
            $this->set($discoverer, is_int($alias) ? null : $alias);
        }
    }

    /**
     * @param Resource $resource
     * @return UriInterface[]
     */
    public function discover(Resource $resource)
    {
        $discoveredUris = array();

        foreach ($this->discoverers as $discoverer) {
            $discoveredUris = array_merge($discoveredUris, $discoverer->discover($resource));
        }

        // TODO: perf improvement: do al this in one loop instead of three
        $this->normalize($discoveredUris);
        $this->removeDuplicates($discoveredUris);
        $this->filter($discoveredUris);

        return $discoveredUris;
    }

    /**
     * Sets a discoverer.
     *
     * @param discovererInterface $discoverer The discoverer instance
     * @param string          $alias  An alias
     */
    public function set(DiscovererInterface $discoverer, $alias = null)
    {
        $this->discoverers[$discoverer->getName()] = $discoverer;
        if (null !== $alias) {
            $this->discoverers[$alias] = $discoverer;
        }

        $discoverer->setDiscovererSet($this);
    }

    /**
     * @param PreFetchFilter $filter
     */
    public function addFilter(PreFetchFilter $filter)
    {
        $this->filters[] = $filter;
    }

    /**
     * Returns true if the discoverer is defined.
     *
     * @param string $name The discoverer name
     *
     * @return bool true if the discoverer is defined, false otherwise
     */
    public function has($name)
    {
        return isset($this->discoverers[$name]);
    }

    /**
     * Gets a discoverer.
     *
     * @param string $name The discoverer name
     *
     * @return Discoverer The discoverer instance
     *
     * @throws InvalidArgumentException if the discoverer is not defined
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('The discoverer "%s" is not defined.', $name));
        }

        return $this->discoverers[$name];
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->discoverers);
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function normalize(array &$discoveredUris)
    {
        foreach ($discoveredUris as &$uri) {
            $uri->normalize();
        }
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function filter(array &$discoveredUris)
    {
        foreach ($discoveredUris as $k => &$uri) {
            $uri = new FilterableUri($uri->toString());
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
        /** @var Uri $uri */
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

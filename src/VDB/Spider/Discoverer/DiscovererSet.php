<?php

namespace VDB\Spider\Discoverer;

use VDB\Spider\Resource;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\FilterableUri;

class DiscovererSet implements \IteratorAggregate
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

    /**
     * @var array the list of already visited URIs with the depth they were discovered on as value
     */
    private $alreadySeenUris = array();

    public function __construct(array $discoverers = array())
    {
        foreach ($discoverers as $alias => $discoverer) {
            $this->set($discoverer, is_int($alias) ? null : $alias);
        }
    }

    /**
     * @param FilterableUri $uri
     *
     * Mark an Uri as already seen.
     *
     * If it already exists, it is not overwritten, since we want to keep the
     * first depth it was found at.
     */
    private function markSeen(FilterableUri $uri)
    {
        $uriString = $uri->normalize()->toString();
        if (!array_key_exists($uriString, $this->alreadySeenUris)) {
            $this->alreadySeenUris[$uriString] = $uri->getDepthFound();
        }
    }

    /**
     * @return bool Returns true if this URI was found at max depth
     */
    private function isAtMaxDepth(FilterableUri $uri)
    {
        if ($uri->getDepthFound() === $this->maxDepth) {
            return true;
        }
        return false;
    }

    /**
     * @param Resource $resource
     * @return UriInterface[]
     */
    public function discover(Resource $resource)
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

        foreach ($discoveredUris as $uri) {
            $uri->setDepthFound($resource->getUri()->getDepthFound() + 1);
            $this->markSeen($uri);
        }

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
     * @param PreFetchFilterInterface $filter
     */
    public function addFilter(PreFetchFilterInterface $filter)
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
    private function filterAlreadySeen(array &$discoveredUris)
    {
        foreach ($discoveredUris as $k => &$uri) {
            if (array_key_exists($uri->toString(), $this->alreadySeenUris)) {
                unset($discoveredUris[$k]);
            }
        }
    }

    /**
     * @param UriInterface[] $discoveredUris
     */
    private function filter(array &$discoveredUris)
    {
        foreach ($discoveredUris as $k => &$uri) {
            $uri = new FilterableUri($uri);
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

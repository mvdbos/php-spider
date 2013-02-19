<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\FilterableURI;
use VDB\URI\HttpURI;
use VDB\URI\URI;
use VDB\Spider\Filter\PreFetchFilter;

/**
 * @author matthijs
 */
class AlreadyVisitedFilter implements PreFetchFilter
{
    /** @var array associative array of all not-skipped URIs */
    private $visited = array();

    /** @var URI */
    private $seed;

    /**
     * @param string $seed
     */
    public function __construct($seed)
    {
        $this->seed = new HttpURI($seed);
    }

    /**
     * @param FilterableURI $uri
     * @return bool
     */
    public function match(FilterableURI $uri)
    {
        if ($uri->recompose() === $this->seed->recompose() || array_key_exists($uri->recompose(), $this->visited)) {
            $uri->setFiltered(true, 'Already Visited');
            return true;
        }

        // If not visited or skipped before, this is the first visit
        $this->visited[$uri->recompose()] = true;
        return false;
    }
}

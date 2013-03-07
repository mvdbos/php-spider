<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\URI\FilterableURI;

/**
 * @author matthijs
 */
class UriWithHashFragmentFilter implements PreFetchFilter
{
    public function match(FilterableURI $uri)
    {
        if (null !== $uri->getFragment()) {
            $uri->setFiltered(true, 'URI with hash fragment');
            return true;
        }
        return false;
    }
}

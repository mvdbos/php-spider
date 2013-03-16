<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class UriWithHashFragmentFilter implements PreFetchFilter
{
    public function match(FilterableUri $uri)
    {
        if (null !== $uri->getFragment()) {
            $uri->setFiltered(true, 'URI with hash fragment');
            return true;
        }
        return false;
    }
}

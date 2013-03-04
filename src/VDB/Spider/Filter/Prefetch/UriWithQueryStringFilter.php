<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\FilterableURI;
use VDB\Spider\Filter\PreFetchFilter;

/**
 * @author matthijs
 */
class UriWithQueryStringFilter implements PreFetchFilter
{
    public function match(FilterableURI $uri)
    {
        if (null !== $uri->getQuery()) {
            $uri->setFiltered(true, 'URI with query string');
            return true;
        }
        return false;
    }
}

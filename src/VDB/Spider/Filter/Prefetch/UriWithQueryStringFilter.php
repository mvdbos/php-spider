<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\URI\FilterableURI;

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

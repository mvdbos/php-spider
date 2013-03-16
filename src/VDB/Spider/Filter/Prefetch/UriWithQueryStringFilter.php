<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilter;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class UriWithQueryStringFilter implements PreFetchFilter
{
    public function match(FilterableUri $uri)
    {
        if (null !== $uri->getQuery()) {
            $uri->setFiltered(true, 'URI with query string');
            return true;
        }
        return false;
    }
}

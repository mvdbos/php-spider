<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */
class UriWithQueryStringFilter implements PreFetchFilterInterface
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

<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * @author matthijs
 */
class UriWithQueryStringFilter implements PreFetchFilterInterface
{
    public function match(UriInterface $uri)
    {
        return null !== $uri->getQuery();
    }
}

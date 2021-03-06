<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class UriWithQueryStringFilter implements PreFetchFilterInterface
{
    public function match(UriInterface $uri): bool
    {
        return null !== $uri->getQuery();
    }
}

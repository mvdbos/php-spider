<?php

namespace VDB\Spider\Filter;

use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
interface PreFetchFilterInterface
{
    /**
     * Returns true of the URI should be filtered out, i.e. NOT be crawled.
     * @param UriInterface $uri
     * @return boolean
     */
    public function match(UriInterface $uri): bool;
}

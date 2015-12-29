<?php

namespace VDB\Spider\Filter;

use VDB\Uri\UriInterface;

/**
 * @author matthijs
 */

interface PreFetchFilterInterface
{
    /**
     * @param UriInterface $uri
     * @return boolean
     */
    public function match(UriInterface $uri);
}

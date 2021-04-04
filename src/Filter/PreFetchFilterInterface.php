<?php

namespace VDB\Spider\Filter;

use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
interface PreFetchFilterInterface
{
    /**
     * @param UriInterface $uri
     * @return boolean
     */
    public function match(UriInterface $uri): bool;
}

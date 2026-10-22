<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
interface PreFetchFilterInterface
{
    /**
     * Returns true of the URI should be filtered out, i.e. NOT be crawled.
     * @param DiscoveredUri $uri
     * @return boolean
     */
    public function match(DiscoveredUri $uri): bool;
}

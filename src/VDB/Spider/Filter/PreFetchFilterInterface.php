<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author matthijs
 */

interface PreFetchFilterInterface
{
    /**
     * @param DiscoveredUri $uri
     * @return boolean
     */
    public function match(DiscoveredUri $uri);
}

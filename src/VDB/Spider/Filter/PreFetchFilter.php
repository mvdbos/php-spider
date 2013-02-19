<?php

namespace VDB\Spider\Filter;

use VDB\Spider\FilterableURI;

/**
 * @author matthijs
 */

interface PreFetchFilter
{
    /**
     * @param \Symfony\Component\DomCrawler\Link $uri
     * @return boolean
     */
    public function match(FilterableURI $uri);
}

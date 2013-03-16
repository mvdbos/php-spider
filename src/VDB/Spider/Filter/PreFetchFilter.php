<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */

interface PreFetchFilter
{
    /**
     * @param \Symfony\Component\DomCrawler\Link $uri
     * @return boolean
     */
    public function match(FilterableUri $uri);
}

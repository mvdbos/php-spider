<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */

interface PreFetchFilter
{
    /**
     * @param FilterableUri $uri
     * @return boolean
     */
    public function match(FilterableUri $uri);
}

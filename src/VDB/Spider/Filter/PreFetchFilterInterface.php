<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Uri\FilterableUri;

/**
 * @author matthijs
 */

interface PreFetchFilterInterface
{
    /**
     * @param FilterableUri $uri
     * @return boolean
     */
    public function match(FilterableUri $uri);
}

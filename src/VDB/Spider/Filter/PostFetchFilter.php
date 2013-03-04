<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Resource;

/**
 * @author matthijs
 */

interface PostFetchFilter
{
    /**
     * @param Resource
     * @return boolean
     */
    public function match(Resource $document);
}

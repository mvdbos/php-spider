<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Resource;

/**
 * @author matthijs
 */

interface PostFetchFilterInterface
{
    /**
     * @param Resource
     * @return boolean
     */
    public function match(Resource $document);
}

<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Resource;

/**
 * @author matthijs
 */
interface PostFetchFilterInterface
{
    /**
     * @param Resource $resource
     * @return boolean
     */
    public function match(Resource $resource): bool;
}

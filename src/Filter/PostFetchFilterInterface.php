<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Resource;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
interface PostFetchFilterInterface
{
    /**
     * @param Resource $resource
     * @return boolean
     */
    public function match(Resource $resource): bool;
}

<?php

namespace VDB\Spider;

/**
 * @author matthijs
 */

interface SkipCondition
{
    /**
     * @param string $uri
     * @return boolean
     */
    public function match($uri);
}
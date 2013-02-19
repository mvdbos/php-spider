<?php

namespace VDB\Spider\Filter;

use VDB\Spider\Document;

/**
 * @author matthijs
 */

interface PostFetchFilter
{
    /**
     * @param Document
     * @return boolean
     */
    public function match(Document $document);
}

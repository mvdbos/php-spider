<?php

namespace VDB\Spider;

/**
 * @author matthijs
 */

interface Processor
{
    /**
     * @param string $uri
     * @return void
     */
    public function execute(Document $document);
}

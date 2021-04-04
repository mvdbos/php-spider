<?php

namespace VDB\Spider\Filter\Postfetch;

use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class MimeTypeFilter implements PostFetchFilterInterface
{
    protected $allowedMimeType = '';

    public function __construct($allowedMimeType)
    {
        $this->allowedMimeType = $allowedMimeType;
    }

    public function match(Resource $resource): bool
    {
        $contentType = $resource->getResponse()->getHeaderLine('Content-Type');
        return $contentType !== $this->allowedMimeType;
    }
}

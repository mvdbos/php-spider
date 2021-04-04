<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * @author matthijs
 */
class AllowedSchemeFilter implements PreFetchFilterInterface
{
    private $allowedSchemes;

    /**
     * @param string[] $schemes
     */
    public function __construct(array $schemes)
    {
        $this->allowedSchemes = $schemes;
    }

    /**
     * @param UriInterface $uri
     * @return bool
     */
    public function match(UriInterface $uri): bool
    {
        return !in_array($uri->getScheme(), $this->allowedSchemes);
    }
}

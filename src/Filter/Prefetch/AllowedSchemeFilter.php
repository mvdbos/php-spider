<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class AllowedSchemeFilter implements PreFetchFilterInterface
{
    private array $allowedSchemes;

    /**
     * @param string[] $schemes
     */
    public function __construct(array $schemes)
    {
        $this->allowedSchemes = $schemes;
    }

    /**
     * @param DiscoveredUri $uri
     * @return bool
     */
    public function match(DiscoveredUri $uri): bool
    {
        return !in_array($uri->getScheme(), $this->allowedSchemes);
    }
}

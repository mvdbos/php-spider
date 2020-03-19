<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * @author matthijs
 */
class AllowedPortsFilter implements PreFetchFilterInterface
{
    /**
     * @var array
     */
    private $allowedPorts;

    /**
     * The whitelist of allowed ports
     * @param array $allowedPorts
     */
    public function __construct(array $allowedPorts)
    {
        $this->allowedPorts = $allowedPorts;
    }

    public function match(UriInterface $uri)
    {
        return !in_array($uri->getPort(), $this->allowedPorts);
    }
}

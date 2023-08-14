<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class AllowedPortsFilter implements PreFetchFilterInterface
{
    /**
     * @var array
     */
    private array $allowedPorts;

    /**
     * The whitelist of allowed ports
     * @param array $allowedPorts
     */
    public function __construct(array $allowedPorts)
    {
        $this->allowedPorts = $allowedPorts;
    }

    public function match(UriInterface $uri): bool
    {
        return !in_array($uri->getPort(), $this->allowedPorts);
    }
}

<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class UriFilter implements PreFetchFilterInterface
{
    /**
     * @var array An array of regexes
     */
    public array $regexes = array();

    public function __construct(array $regexes = array())
    {
        $this->regexes = $regexes;
    }

    public function match(DiscoveredUri $uri): bool
    {
        foreach ($this->regexes as $regex) {
            if (preg_match($regex, $uri->toString())) {
                return true;
            }
        }
        return false;
    }
}

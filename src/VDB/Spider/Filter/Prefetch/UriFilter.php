<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author matthijs
 */
class UriFilter implements PreFetchFilterInterface
{
    /**
     * @var array An array of regexes
     */
    public $regexes = array();

    public function __construct(array $regexes = array())
    {
        $this->regexes = $regexes;
    }

    public function match(DiscoveredUri $uri)
    {
        foreach ($this->regexes as $regex) {
            if (preg_match($regex, $uri->toString())) {
                return true;
            }
        }
        return false;
    }
}

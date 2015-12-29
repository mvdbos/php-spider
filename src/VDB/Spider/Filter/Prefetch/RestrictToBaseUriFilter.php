<?php
namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\Uri;
use VDB\Uri\UriInterface;

/**
 * @author matthijs
 */
class RestrictToBaseUriFilter implements PreFetchFilterInterface
{
    /** @var Uri */
    private $seed;

    /**
     * @param string $seed
     */
    public function __construct($seed)
    {
        $this->seed = new Uri($seed);
    }

    public function match(UriInterface $uri)
    {
        /*
         * if the URI does not contain the seed, it is not allowed
         */
        return false === stripos($uri->toString(), $this->seed->toString());
    }
}

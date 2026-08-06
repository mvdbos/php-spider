<?php

namespace VDB\Spider\Filter\Prefetch;

use InvalidArgumentException;
use Uri\InvalidUriException;
use Uri\Rfc3986\Uri;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class RestrictToBaseUriFilter implements PreFetchFilterInterface
{
    /** @var Uri */
    private Uri $seed;

    /**
     * @param string $seed
     */
    public function __construct(string $seed)
    {
        try {
            $this->seed = new Uri($seed);
        } catch (InvalidUriException $e) {
            throw new InvalidArgumentException("Invalid seed: " . $e->getMessage());
        }
    }

    public function match(DiscoveredUri $uri): bool
    {
        /*
         * if the URI does not contain the seed, it is not allowed
         */
        return false === stripos($uri->toString(), $this->seed->toString());
    }
}

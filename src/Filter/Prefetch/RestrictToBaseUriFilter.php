<?php

namespace VDB\Spider\Filter\Prefetch;

use ErrorException;
use InvalidArgumentException;
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class RestrictToBaseUriFilter implements PreFetchFilterInterface
{
    /** @var Uri */
    private $seed;

    /**
     * @param string $seed
     */
    public function __construct(string $seed)
    {
        try {
            $this->seed = new Uri($seed);
        } catch (ErrorException | UriSyntaxException $e) {
            throw new InvalidArgumentException("Invalid seed: " . $e->getMessage());
        }
    }

    public function match(UriInterface $uri): bool
    {
        /*
         * if the URI does not contain the seed, it is not allowed
         */
        return false === stripos($uri->toString(), $this->seed->toString());
    }
}

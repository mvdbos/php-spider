<?php

namespace VDB\Spider\Uri;

use Uri\Rfc3986\Uri;
use Uri\UriComparisonMode;

class DiscoveredUri
{
    protected Uri $decorated;
    private int $depthFound;

    public function __construct(Uri|string $decorated, int $depthFound)
    {
        if (!$decorated instanceof Uri) {
            $decorated = new Uri($decorated);
        }

        $this->decorated = $decorated;
        $this->depthFound = $depthFound;
    }

    /**
     * @return int The depth this Uri was found on
     */
    public function getDepthFound(): int
    {
        return $this->depthFound;
    }

    // @codeCoverageIgnoreStart
    // We ignore coverage for all proxy methods below:
    // the constructor is tested and if that is successful there is no point
    // to testing the behaviour of the decorated class

    public function toString(): string
    {
        return $this->decorated->toString();
    }

    public function equals(DiscoveredUri $that): bool
    {
        return $this->decorated->equals($that->decorated, UriComparisonMode::IncludeFragment);
    }

    /**
     * Resolves an (absolute or relative) URI reference against this Uri.
     */
    public function resolve(string $uri): Uri
    {
        return $this->decorated->resolve($uri);
    }

    /**
     * Alias of DiscoveredUri::toString()
     */
    public function __toString(): string
    {
        return $this->decorated->toString();
    }

    public function getHost(): ?string
    {
        return $this->decorated->getHost();
    }

    public function getPassword(): ?string
    {
        return $this->decorated->getPassword();
    }

    public function getPath(): string
    {
        return $this->decorated->getPath();
    }

    public function getPort(): ?int
    {
        return $this->decorated->getPort();
    }

    public function getQuery(): ?string
    {
        return $this->decorated->getQuery();
    }

    public function getScheme(): ?string
    {
        return $this->decorated->getScheme();
    }

    public function getUsername(): ?string
    {
        return $this->decorated->getUsername();
    }

    public function getFragment(): ?string
    {
        return $this->decorated->getFragment();
    }

    // @codeCoverageIgnoreEnd
}

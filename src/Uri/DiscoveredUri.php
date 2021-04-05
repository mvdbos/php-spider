<?php

namespace VDB\Spider\Uri;

use ErrorException;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;
use VDB\Uri\UriInterface;

class DiscoveredUri implements UriInterface
{
    /**
     * @var UriInterface
     */
    protected $decorated;

    /** @var int */
    private $depthFound;

    /**
     * @param string|UriInterface $decorated
     * @param int $depthFound
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function __construct($decorated, int $depthFound)
    {
        if (!$decorated instanceof UriInterface) {
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

//    /**
//     * @param int $depthFound The depth this Uri was found on
//     */
//    public function setDepthFound(int $depthFound)
//    {
//        $this->depthFound = $depthFound;
//    }

    // @codeCoverageIgnoreStart
    // We ignore coverage for all proxy methods below:
    // the constructor is tested and if that is successful there is no point
    // to testing the behaviour of the decorated class

    public function toString(): string
    {
        return $this->decorated->toString();
    }

    /**
     * @param UriInterface $that
     * @param boolean $normalized whether to compare normalized versions of the URIs
     * @return boolean
     */
    public function equals(UriInterface $that, $normalized = false): bool
    {
        return $this->decorated->equals($that, $normalized);
    }

    /**
     * @return UriInterface
     */
    public function normalize(): UriInterface
    {
        // This normalizes the decorated Uri in place. We don't want to return the decorated Uri, but $this.
        $this->decorated->normalize();
        return $this;
    }

    /**
     * Alias of Uri::toString()
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->decorated->__toString();
    }

    /**
     * @return string|null
     */
    public function getHost(): ?string
    {
        return $this->decorated->getHost();
    }

    /**
     * @return string|null
     */
    public function getPassword(): ?string
    {
        return $this->decorated->getPassword();
    }

    /**
     * @return string|null
     */
    public function getPath(): ?string
    {
        return $this->decorated->getPath();
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->decorated->getPort();
    }

    /**
     * @return string|null
     */
    public function getQuery(): ?string
    {
        return $this->decorated->getQuery();
    }

    /**
     * @return string|null
     */
    public function getScheme(): ?string
    {
        return $this->decorated->getScheme();
    }

    /**
     * @return string|null
     */
    public function getUsername(): ?string
    {
        return $this->decorated->getUsername();
    }

    /**
     * @return string|null
     */
    public function getFragment(): ?string
    {
        return $this->decorated->getFragment();
    }

    // @codeCoverageIgnoreEnd
}

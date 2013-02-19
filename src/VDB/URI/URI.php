<?php
namespace VDB\URI;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
interface URI
{
    /**
     * @param string $uri
     * @throws VDB\URI\Exception\UriSyntaxException
     *
     * RFC 3986
     */
    public function __construct($uri, $baseUri = null);

    /**
     * Returns the content of this URI as a string.
     *
     * A string equivalent to the original input string, or to the
     * string computed from the original string, as appropriate, is
     * returned.  This can be influence bij normalization, reference resolution,
     * and so a string is constructed from this URI's components according to
     * the rules specified in RFC 3986 paragraph 5.3
     *
     * @return string The string form of this URI
     */
    public function recompose();

    /**
     * Alias of URI::recompose()
     *
     * @return string
     */
    public function __toString();

    /**
     * @return string|null
     */
    public function getHost();

    /**
     * @return string|null
     */
    public function getPassword();

    /**
     * @return string|null
     */
    public function getPath();

    /**
     * @return int|null
     */
    public function getPort();

    /**
     * @return string|null
     */
    public function getQuery();

    /**
     * @return string|null
     */
    public function getScheme();

    /**
     * @return string|null
     */
    public function getUsername();

    /**
     * @return string|null
     */
    public function getFragment();
}

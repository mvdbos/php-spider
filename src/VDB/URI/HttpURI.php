<?php
namespace VDB\URI;

use VDB\URI\GenericURI;
use VDB\URI\Exception\UriSyntaxException;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 *
 * Based on RFC 3986,
 * Amended with HTTP URI scheme from RFC 2616 paragraph 3.2
 *
 * Note: different from RFC 3986, empty path should become '/';
 *
 */
class HttpURI extends GenericURI
{
    public static $allowedSchemes = array('http');

    protected function validateScheme()
    {
        parent::validateScheme();
        if (!in_array($this->scheme, static::$allowedSchemes)) {
            throw new UriSyntaxException('Only HTTP scheme allowed');
        }
    }

    protected function doSchemeSpecificPostProcessing()
    {
        if (null === $this->path) {
            $this->path = '/';
        }
    }
}

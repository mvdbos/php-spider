<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests;

use Guzzle\Http\Message\Response;
use VDB\Spider\Resource;
use VDB\URI\GenericURI;

/**
 *
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param GenericURI $uri
     * @param Response $response
     * @return Resource
     */
    protected function getResource(GenericURI $uri, Response $response)
    {
        return new Resource($uri, $response);
    }
}

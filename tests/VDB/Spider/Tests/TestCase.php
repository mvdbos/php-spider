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

use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

/**
 *
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param DiscoveredUri $uri
     * @param Response $response
     * @return Resource
     */
    protected function getResource(DiscoveredUri $uri, Response $response)
    {
        return new Resource($uri, $response);
    }

    protected function buildResourceFromFixture($fixturePath, $uriString)
    {
        return $this->getResource(
            new DiscoveredUri(new Uri($uriString)),
            new Response(200, [], $this->getFixtureContent($fixturePath))
        );
    }

    /**
     * @param $filePath /absolute/path/to/fixure
     */
    protected function getFixtureContent($filePath)
    {
        return file_get_contents($filePath);
    }
}

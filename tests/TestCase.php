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

use ErrorException;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;

/**
 *
 */
class TestCase extends PHPUnitTestCase
{
    /**
     * @param DiscoveredUri $uri
     * @param Response $response
     * @return Resource
     */
    protected function getResource(DiscoveredUri $uri, Response $response): Resource
    {
        return new Resource($uri, $response);
    }

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    protected function buildResourceFromFixture($fixturePath, $uriString): Resource
    {
        return $this->getResource(
            new DiscoveredUri(new Uri($uriString), 0),
            new Response(200, [], $this->getFixtureContent($fixturePath))
        );
    }

    /**
     * @param $filePath /absolute/path/to/fixture
     */
    protected function getFixtureContent($filePath)
    {
        return file_get_contents($filePath);
    }
}

<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\RequestHandler;

use GuzzleHttp\Psr7\Response;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class GuzzleRequestHandlerTest extends TestCase
{
    /**
     * @covers \VDB\Spider\RequestHandler\GuzzleRequestHandler
     */
    public function testCustomClient()
    {
        $uri = 'http://example.com';
        $expectedResponse = new Response(200, [], "Test");
        $client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $client
            ->expects($this->once())
            ->method('get')
            ->with($uri)
            ->will($this->returnValue($expectedResponse));

        $handler = new GuzzleRequestHandler($client);
        $actualResponse = $handler->request(new DiscoveredUri($uri, 0))->getResponse();
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}

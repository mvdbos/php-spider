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

use PHPUnit\Framework\Attributes\CoversClass;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversClass(\VDB\Spider\RequestHandler\GuzzleRequestHandler::class)]
class GuzzleRequestHandlerTest extends TestCase
{
    public function testCustomClient()
    {
        $uri = 'http://example.com';
        $expectedResponse = new Response(200, [], "Test");
        $client = $this->getMockBuilder('GuzzleHttp\Client')->getMock();
        $client
            ->expects($this->once())
            ->method('get')
            ->with($uri)
            ->willReturn($expectedResponse);

        $handler = new GuzzleRequestHandler($client);
        $actualResponse = $handler->request(new DiscoveredUri($uri, 0))->getResponse();
        $this->assertEquals($expectedResponse, $actualResponse);
    }
}

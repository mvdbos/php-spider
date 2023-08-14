<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\PersistenceHandler;

use ErrorException;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\PersistenceHandler\MemoryPersistenceHandler;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

class MemoryPersistenceHandlerTest extends TestCase
{
    /**
     * @var MemoryPersistenceHandler
     */
    protected MemoryPersistenceHandler $handler;

    public function setUp(): void
    {
        $this->handler = new MemoryPersistenceHandler();
        $this->handler->setSpiderId('spider-UT');
    }

    /**
     * @covers       \VDB\Spider\PersistenceHandler\MemoryPersistenceHandler
     * @covers       \VDB\Spider\PersistenceHandler\FilePersistenceHandler
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testPersist()
    {
        $resource1 = new Resource(
            new DiscoveredUri("http://example.com/1", 0),
            new Response(200, [], "Test Body Contents 1")
        );

        $resource2 = new Resource(
            new DiscoveredUri("http://example.com/1", 0),
            new Response(200, [], "Test Body Contents 2")
        );

        $expectedResources = [$resource1, $resource2];

        $this->handler->persist($resource1);
        $this->handler->persist($resource2);

        $this->assertEquals(2, $this->handler->count());

        // Check the contents through iterator access and directly
        foreach ($this->handler as $path => $resource) {
            $this->assertEquals(
                $expectedResources[$path],
                $resource
            );
        }
    }
}

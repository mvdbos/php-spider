<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\QueueManager;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use ErrorException;
use InvalidArgumentException;
use VDB\Spider\Exception\MaxQueueSizeExceededException;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

#[CoversClass(\VDB\Spider\QueueManager\InMemoryQueueManager::class)]
#[CoversMethod(\VDB\Spider\QueueManager\InMemoryQueueManager::class, 'setMaxQueueSize')]
#[CoversMethod(\VDB\Spider\QueueManager\InMemoryQueueManager::class, 'getMaxQueueSize')]
class InMemoryQueueManagerTest extends TestCase
{
    public function testInvalidTraversalAlgo()
    {
        $this->expectException(InvalidArgumentException::class);
        new InMemoryQueueManager(53242);
    }

    public function testSetTraversalAlgo()
    {
        $qm = new InMemoryQueueManager(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
        $this->assertEquals(QueueManagerInterface::ALGORITHM_BREADTH_FIRST, $qm->getTraversalAlgorithm());
    }

    /**
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testMaxQueueSizeExceeded()
    {
        $this->expectException(MaxQueueSizeExceededException::class);
        $qm = new InMemoryQueueManager();
        $qm->setMaxQueueSize(1);
        $qm->addUri(new DiscoveredUri("foo", 0));
        $qm->addUri(new DiscoveredUri("bar", 0));
    }

    /**
     * @throws ErrorException
     * @throws UriSyntaxException
     * @throws MaxQueueSizeExceededException
     */
    public function testDepthFirst()
    {
        $qm = new InMemoryQueueManager();
        $uri1 = new DiscoveredUri("foo", 0);
        $uri2  = new DiscoveredUri("bar", 0);
        $uri3  = new DiscoveredUri("baz", 0);
        $qm->addUri($uri1);
        $qm->addUri($uri2);
        $qm->addUri($uri3);

        $this->assertEquals($uri3, $qm->next());
        $this->assertEquals($uri2, $qm->next());
    }

    /**
     * @throws ErrorException
     * @throws UriSyntaxException
     * @throws MaxQueueSizeExceededException
     */
    public function testBreadthFirst()
    {
        $qm = new InMemoryQueueManager(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
        $uri1 = new DiscoveredUri("foo", 0);
        $uri2  = new DiscoveredUri("bar", 0);
        $uri3  = new DiscoveredUri("baz", 0);
        $qm->addUri($uri1);
        $qm->addUri($uri2);
        $qm->addUri($uri3);

        $this->assertEquals($uri1, $qm->next());
        $this->assertEquals($uri2, $qm->next());
    }

    public function testSetAndGetMaxQueueSize()
    {
        $qm = new InMemoryQueueManager();
        $this->assertEquals(0, $qm->getMaxQueueSize());
        
        $qm->setMaxQueueSize(10);
        $this->assertEquals(10, $qm->getMaxQueueSize());
    }

    /**
     * An exhausted queue returns null for both traversal algorithms.
     */
    public function testNextOnEmptyQueue(): void
    {
        $depthFirst = new InMemoryQueueManager();
        $this->assertNull($depthFirst->next());

        $breadthFirst = new InMemoryQueueManager(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
        $this->assertNull($breadthFirst->next());
    }
}

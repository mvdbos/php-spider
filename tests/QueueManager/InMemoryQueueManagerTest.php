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

use ErrorException;
use InvalidArgumentException;
use VDB\Spider\Exception\MaxQueueSizeExceededException;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

/**
 *
 */
class InMemoryQueueManagerTest extends TestCase
{
    /**
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager
     */
    public function testInvalidTraversalAlgo()
    {
        $this->expectException(InvalidArgumentException::class);
        new InMemoryQueueManager(53242);
    }

    /**
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager
     */
    public function testSetTraversalAlgo()
    {
        $qm = new InMemoryQueueManager(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
        $this->assertEquals(QueueManagerInterface::ALGORITHM_BREADTH_FIRST, $qm->getTraversalAlgorithm());
    }

    /**
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testMaxQueueSizeExceeded()
    {
        $this->expectException(MaxQueueSizeExceededException::class);
        $qm = new InMemoryQueueManager();
        $qm->maxQueueSize = 1;
        $qm->addUri(new DiscoveredUri("foo", 0));
        $qm->addUri(new DiscoveredUri("bar", 0));
    }

    /**
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager
     *
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
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager
     *
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

    /**
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager::setMaxQueueSize
     * @covers \VDB\Spider\QueueManager\InMemoryQueueManager::getMaxQueueSize
     */
    public function testSetAndGetMaxQueueSize()
    {
        $qm = new InMemoryQueueManager();
        $this->assertEquals(0, $qm->getMaxQueueSize());
        
        $qm->setMaxQueueSize(10);
        $this->assertEquals(10, $qm->getMaxQueueSize());
    }
}

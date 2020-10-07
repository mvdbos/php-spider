<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Discoverer;

use VDB\Spider\Tests\TestCase;

/**
 *
 */
class DiscovererTest extends TestCase
{
    /**
     * @covers VDB\Spider\Discoverer\Discoverer
     */
    public function testGetName()
    {
        $stub = $this->getMockBuilder('VDB\\Spider\\Discoverer\\Discoverer')
            ->setMockClassName('MockDiscoverer')
            ->getMockForAbstractClass();

        $this->assertEquals('MockDiscoverer', $stub->getName());
    }
}

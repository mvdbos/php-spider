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

use PHPUnit\Framework\Attributes\CoversClass;
use VDB\Spider\Discoverer\Discoverer;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;

#[CoversClass(\VDB\Spider\Discoverer\Discoverer::class)]
class DiscovererTest extends TestCase
{
    public function testGetName()
    {
        $stub = new class extends Discoverer {
            public function discover(Resource $resource): array
            {
                return [];
            }
        };

        $this->assertEquals(get_class($stub), $stub->getName());
    }
}

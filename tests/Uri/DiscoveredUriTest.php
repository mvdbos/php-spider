<?php
/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Uri;

use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class DiscoveredUriTest extends TestCase
{
    /**
     * @covers VDB\Spider\Uri\DiscoveredUri
     */
    public function testDepthFound()
    {
        $uri = new DiscoveredUri('http://example.org');
        $uri->setDepthFound(12);
        $this->assertEquals(12, $uri->getDepthFound());
    }
}

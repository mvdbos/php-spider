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

use ErrorException;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

/**
 *
 */
class DiscoveredUriTest extends TestCase
{
    /**
     * @covers \VDB\Spider\Uri\DiscoveredUri
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testDepthFound()
    {
        $uri = new DiscoveredUri('http://example.org', 12);
        $this->assertEquals(12, $uri->getDepthFound());
    }
}

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

use PHPUnit\Framework\Attributes\CoversClass;
use ErrorException;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

#[CoversClass(\VDB\Spider\Uri\DiscoveredUri::class)]
class DiscoveredUriTest extends TestCase
{
    /**
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testDepthFound()
    {
        $uri = new DiscoveredUri('http://example.org', 12);
        $this->assertEquals(12, $uri->getDepthFound());
    }

    /**
     * Normalization is idempotent and memoized: a second call returns the same
     * instance and leaves the string form of the Uri untouched.
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testNormalizeIsIdempotent()
    {
        $uri = new DiscoveredUri('HTTP://EXAMPLE.ORG/a/../b/./c', 0);

        $this->assertSame($uri, $uri->normalize());
        $normalizedString = $uri->toString();
        $this->assertEquals('http://example.org/b/c', $normalizedString);

        // calling normalize() again should be a no-op
        $this->assertSame($uri, $uri->normalize());
        $this->assertEquals($normalizedString, $uri->toString());
    }
}

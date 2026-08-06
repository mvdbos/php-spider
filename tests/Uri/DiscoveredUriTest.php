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
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversClass(\VDB\Spider\Uri\DiscoveredUri::class)]
class DiscoveredUriTest extends TestCase
{
    public function testDepthFound()
    {
        $uri = new DiscoveredUri('http://example.org', 12);
        $this->assertEquals(12, $uri->getDepthFound());
    }

    public function testProxyMethods()
    {
        $uri = new DiscoveredUri('http://user:pass@example.org:8080/path?q=1#frag', 0);

        $this->assertEquals('http', $uri->getScheme());
        $this->assertEquals('example.org', $uri->getHost());
        $this->assertEquals(8080, $uri->getPort());
        $this->assertEquals('/path', $uri->getPath());
        $this->assertEquals('q=1', $uri->getQuery());
        $this->assertEquals('frag', $uri->getFragment());
        $this->assertEquals('user', $uri->getUsername());
        $this->assertEquals('pass', $uri->getPassword());
        $this->assertEquals('http://user:pass@example.org:8080/path?q=1#frag', $uri->toString());
        $this->assertEquals('http://user:pass@example.org:8080/path?q=1#frag', (string) $uri);
    }

    public function testResolve()
    {
        $uri = new DiscoveredUri('http://example.org/dir/page.html', 0);

        $this->assertEquals('http://example.org/dir/other.html', $uri->resolve('other.html')->toString());
        $this->assertEquals('http://other.org/abs', $uri->resolve('http://other.org/abs')->toString());
    }

    public function testEquals()
    {
        $uri1 = new DiscoveredUri('http://example.org/path', 0);
        $uri2 = new DiscoveredUri('http://example.org/path', 3);
        $uri3 = new DiscoveredUri('http://example.org/other', 0);

        $this->assertTrue($uri1->equals($uri2), 'Depth found should not affect equality');
        $this->assertFalse($uri1->equals($uri3));
    }
}

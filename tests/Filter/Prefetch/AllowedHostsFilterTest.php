<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@php-spider.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Filter\Prefetch;

use PHPUnit\Framework\Attributes\CoversClass;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversClass(\VDB\Spider\Filter\Prefetch\AllowedHostsFilter::class)]
class AllowedHostsFilterTest extends TestCase
{
    public function testMatchFullHostname()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org'));

        $uri1 = new DiscoveredUri('http://example.org', 0);
        $uri2 = new DiscoveredUri('http://php-spider.org', 0);
        $uri3 = new DiscoveredUri('http://blog.php-spider.org', 0);

        $this->assertTrue($filter->match($uri1));
        $this->assertTrue($filter->match($uri2));
        $this->assertFalse($filter->match($uri3));
    }

    public function testMatchSubdomain()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org'), true);

        $uri1 = new DiscoveredUri('http://example.com', 0);
        $uri2 = new DiscoveredUri('http://blog.php-spider.org', 0);
        $uri3 = new DiscoveredUri('http://test.php-spider.org', 0);
        $uri4 = new DiscoveredUri('http://php-spider.org', 0);

        $this->assertTrue($filter->match($uri1));
        $this->assertFalse($filter->match($uri2));
        $this->assertFalse($filter->match($uri3));
        $this->assertFalse($filter->match($uri4));
    }

    public function testMatchMultipleDomainsAllowed()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org', 'http://example.com'), true);

        $uri1 = new DiscoveredUri('http://example.com', 0);
        $uri2 = new DiscoveredUri('http://test.example.com', 0);
        $uri3 = new DiscoveredUri('http://blog.php-spider.org', 0);
        $uri4 = new DiscoveredUri('http://test.php-spider.org', 0);
        $uri5 = new DiscoveredUri('http://php-spider.org', 0);
        $uri6 = new DiscoveredUri('http://example.org', 0);

        $this->assertFalse($filter->match($uri1));
        $this->assertFalse($filter->match($uri2));
        $this->assertFalse($filter->match($uri3));
        $this->assertFalse($filter->match($uri4));
        $this->assertFalse($filter->match($uri5));
        $this->assertTrue($filter->match($uri6));
    }

    public function testMatchUriWithoutHostIsSkipped()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org'));

        $uri = new DiscoveredUri('mailto:foo@example.com', 0);

        $this->assertTrue($filter->match($uri));
    }
}

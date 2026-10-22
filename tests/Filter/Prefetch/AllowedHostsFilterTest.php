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
use VDB\Uri\Uri;

#[CoversClass(\VDB\Spider\Filter\Prefetch\AllowedHostsFilter::class)]
class AllowedHostsFilterTest extends TestCase
{
    public function testMatchFullHostname()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org'));

        $uri1 = new Uri('http://example.org');
        $uri2 = new Uri('http://php-spider.org');
        $uri3 = new Uri('http://blog.php-spider.org');

        $this->assertTrue($filter->match($uri1));
        $this->assertTrue($filter->match($uri2));
        $this->assertFalse($filter->match($uri3));
    }

    public function testMatchSubdomain()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org'), true);

        $uri1 = new Uri('http://example.com');
        $uri2 = new Uri('http://blog.php-spider.org');
        $uri3 = new Uri('http://test.php-spider.org');
        $uri4 = new Uri('http://php-spider.org');

        $this->assertTrue($filter->match($uri1));
        $this->assertFalse($filter->match($uri2));
        $this->assertFalse($filter->match($uri3));
        $this->assertFalse($filter->match($uri4));
    }

    public function testMatchMultipleDomainsAllowed()
    {
        $filter = new AllowedHostsFilter(array('http://blog.php-spider.org', 'http://example.com'), true);

        $uri1 = new Uri('http://example.com');
        $uri2 = new Uri('http://test.example.com');
        $uri3 = new Uri('http://blog.php-spider.org');
        $uri4 = new Uri('http://test.php-spider.org');
        $uri5 = new Uri('http://php-spider.org');
        $uri6 = new Uri('http://example.org');

        $this->assertFalse($filter->match($uri1));
        $this->assertFalse($filter->match($uri2));
        $this->assertFalse($filter->match($uri3));
        $this->assertFalse($filter->match($uri4));
        $this->assertFalse($filter->match($uri5));
        $this->assertTrue($filter->match($uri6));
    }
}

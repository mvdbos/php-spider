<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Filter\Prefetch;

use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class UriWithQueryStringFilterTest extends TestCase
{
    /**
     * @covers \VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter
     */
    public function testMatch()
    {
        $filter = new UriWithQueryStringFilter();

        $currentUri = new DiscoveredUri('http://php-spider.org', 0);
        $uri1 = new DiscoveredUri($currentUri->resolve('?'), 0);
        $uri2 = new DiscoveredUri($currentUri->resolve('?foo=2'), 0);
        $uri3 = new DiscoveredUri('http://php-spider.org/foo?bar=baz', 0);
        $uri4 = new DiscoveredUri('http://php-spider.org/foo/?bar=baz', 0);
        $uri5 = new DiscoveredUri('http://php-spider.org?/foo/bar', 0);

        $this->assertTrue($filter->match($uri1), '->match(\'?\')');
        $this->assertTrue($filter->match($uri2));
        $this->assertTrue($filter->match($uri3));
        $this->assertTrue($filter->match($uri4));
        $this->assertTrue($filter->match($uri5));
    }
}

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

use VDB\Spider\Filter\Prefetch\AllowedPortsFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;

/**
 *
 */
class AllowedPortsFilterTest extends TestCase
{
    /**
     * @covers VDB\Spider\Filter\Prefetch\AllowedPortsFilter
     */
    public function testMatchPort()
    {
        $filter = new AllowedPortsFilter(array(8080));

        $uri1 = new Uri('http://example.org');
        $uri2 = new Uri('http://php-spider.org:8080');
        $uri3 = new Uri('http://blog.php-spider.org');

        $this->assertTrue($filter->match($uri1));
        $this->assertFalse($filter->match($uri2));
        $this->assertTrue($filter->match($uri3));
    }
}

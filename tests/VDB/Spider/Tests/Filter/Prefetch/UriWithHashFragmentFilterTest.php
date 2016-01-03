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

use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;

/**
 *
 */
class UriWithHashFragmentFilterTest extends TestCase
{
    /**
     * @covers VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter
     */
    public function testMatch()
    {
        $filter = new UriWithHashFragmentFilter();

        $currentUri = 'http://php-spider.org';
        $uri1 = new Uri('#', $currentUri);
        $uri2 = new Uri('#foo', $currentUri);
        $uri3 = new Uri('http://php-spider.org/foo#bar', $currentUri);
        $uri4 = new Uri('http://php-spider.org/foo/#bar', $currentUri);
        $uri5 = new Uri('http://php-spider.org#/foo/bar', $currentUri);

        $this->assertTrue($filter->match($uri1), '# filtered');
        $this->assertTrue($filter->match($uri2), '#foo');
        $this->assertTrue($filter->match($uri3), 'http://php-spider.org/foo#bar');
        $this->assertTrue($filter->match($uri4), 'http://php-spider.org/foo/#bar');
        $this->asserttrue($filter->match($uri5), 'http://php-spider.org#/foo/bar');
    }
}

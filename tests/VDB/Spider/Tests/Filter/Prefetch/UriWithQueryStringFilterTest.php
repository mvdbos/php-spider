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
use VDB\Uri\Uri;

/**
 *
 */
class UriWithQueryStringFilterTest extends TestCase
{
    /**
     * @covers VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter
     */
    public function testMatch()
    {
        $filter = new UriWithQueryStringFilter();

        $currentUri = 'http://php-spider.org';
        $uri1 = new Uri('?', $currentUri);
        $uri2 = new Uri('?foo=2', $currentUri);
        $uri3 = new Uri('http://php-spider.org/foo?bar=baz', $currentUri);
        $uri4 = new Uri('http://php-spider.org/foo/?bar=baz', $currentUri);
        $uri5 = new Uri('http://php-spider.org?/foo/bar', $currentUri);

        $this->assertTrue($filter->match($uri1), '->match(\'?\')');
        $this->assertTrue($filter->match($uri2));
        $this->assertTrue($filter->match($uri3));
        $this->assertTrue($filter->match($uri4));
        $this->assertTrue($filter->match($uri5));
    }
}

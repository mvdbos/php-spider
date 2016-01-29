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

use VDB\Spider\Filter\Prefetch\RestrictToBaseUriFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;

/**
 *
 */
class RestrictToBaseUriFilterTest extends TestCase
{
    /**
     * @covers VDB\Spider\Filter\Prefetch\RestrictToBaseUriFilter
     * @dataProvider matchURIProvider
     */
    public function testMatch($href, $expected)
    {
        $filter = new RestrictToBaseUriFilter('http://php-spider.org');

        $uri = new Uri($href);

        $this->assertEquals($expected, $filter->match($uri));
    }

    public function matchURIProvider()
    {
        return array(
            array('http://example.org', true),
            array('http://php-spider.org', false),
            array('http://blog.php-spider.org', true),

        );
    }
}

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

use VDB\Spider\Filter\Prefetch\AlreadyVisitedFilter;
use VDB\Spider\FilterableURI;
use VDB\Spider\Tests\TestCase;

/**
 *
 */
class AlreadyVisitedFilterTest extends TestCase
{
    /**
     * @covers VDB\Spider\Filter\Prefetch\AlreadyVisitedFilter::match()
     */
    public function testMatchSeedOnFirstVisit()
    {
        $filter = new AlreadyVisitedFilter('http://php-spider.org');

        $uri = new FilterableURI('http://php-spider.org');

        $this->assertTrue($filter->match($uri));
    }

    /**
     * @covers VDB\Spider\Filter\Prefetch\AlreadyVisitedFilter::match()
     */
    public function testMatchOnSecondVisit()
    {
        $filter = new AlreadyVisitedFilter('http://php-spider.org');

        $uri = new FilterableURI('http://php-spider.org/crawl');

        $this->assertFalse($filter->match($uri));
        $this->assertTrue($filter->match($uri));
    }
}

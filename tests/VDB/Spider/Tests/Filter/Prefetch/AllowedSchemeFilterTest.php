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

use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\FilterableURI;
use VDB\Uri\Http;

/**
 *
 */
class AllowedSchemeFilterTest extends TestCase
{
    /**
     * @covers VDB\Spider\Filter\Prefetch\AllowedSchemeFilter::match()
     */
    public function testMatch()
    {
        Http::$allowedSchemes[] = 'https';
        Http::$allowedSchemes[] = 'mailto';

        $filter = new AllowedSchemeFilter(array('http'));

        $currentUri = 'http://php-spider.org';
        $uri = new FilterableURI('http://php-spider.org');
        $uri2 = new FilterableURI('https://php-spider.org');
        $uri3 = new FilterableURI('#', $currentUri);
        $uri4 = new FilterableURI('mailto:info@example.org');

        $this->assertFalse($filter->match($uri), 'HTTP scheme filtered');
        $this->assertTrue($filter->match($uri2), 'HTTPS scheme filtered');
        $this->assertFalse($filter->match($uri3), 'empty/no scheme filtered');
        $this->assertTrue($filter->match($uri4), 'MAILTO scheme filtered');
    }
}

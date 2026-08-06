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
use VDB\Spider\Filter\Prefetch\UriFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class UriFilterTest extends TestCase
{
    /**
     * @param string[] $regexes
     * @param string $href
     * @param bool $expected
     *
     * @covers       \VDB\Spider\Filter\Prefetch\UriFilter
     * @dataProvider matchURIProvider
     */
    public function testMatch(array $regexes, string $href, bool $expected)
    {
        $filter = new UriFilter($regexes);
        $uri = new DiscoveredUri($href, 0);
        $this->assertEquals($expected, $filter->match($uri));
    }

    public function matchURIProvider(): array
    {
        return array(
            array(['/^.*\.com$/'], 'http://example.com', true),
            array(['/^.*\.org$/'], 'http://example.com', false),
            array(['/^.*\.bogus$/', '/^.*\.com$/'], 'http://example.com', true),
            array(['/^https:\/\/.*$/'], 'https://blog.php-spider.org', true),
        );
    }
}

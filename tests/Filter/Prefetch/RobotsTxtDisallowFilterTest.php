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
use PHPUnit\Framework\Attributes\DataProvider;
use VDB\Spider\Filter\Prefetch\ExtractRobotsTxtException;
use VDB\Spider\Filter\Prefetch\FetchRobotsTxtException;
use VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversClass(\VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter::class)]
class RobotsTxtDisallowFilterTest extends TestCase
{
    public function testNoRobotsTxt()
    {
        $bogusDomain = "http://bar/baz";
        $this->expectException(FetchRobotsTxtException::class);
        new RobotsTxtDisallowFilter($bogusDomain);
    }

    public function testUnsupportedUrlScheme()
    {
        $unsupported = "ftp://example.com";
        $this->expectException(ExtractRobotsTxtException::class);
        new RobotsTxtDisallowFilter($unsupported);
    }

    #[DataProvider('userAgentMatchURIProvider')]
    public function testUserAgentMatch(DiscoveredUri $href, bool $expected)
    {
        $robotsTxtFilter = new RobotsTxtDisallowFilter(seedUrl: "file://" . __DIR__, userAgent: 'PHP-Spider');
        $this->assertEquals($expected, $robotsTxtFilter->match($href));
    }

    #[DataProvider('noUserAgentMatchURIProvider')]
    public function testNoUserAgentMatch(DiscoveredUri $href, bool $expected)
    {
        $robotsTxtFilter = new RobotsTxtDisallowFilter(seedUrl: "file://" . __DIR__);
        $this->assertEquals($expected, $robotsTxtFilter->match($href));
    }

    public static function noUserAgentMatchURIProvider(): array
    {
        return array(
            array(new DiscoveredUri('http://example.com', 0), false),
            array(new DiscoveredUri('http://example.com/foo', 0), true),
            array(new DiscoveredUri('http://example.com/bar', 0), false),
        );
    }

    public static function userAgentMatchURIProvider(): array
    {
        return array(
            array(new DiscoveredUri('http://example.com', 0), false),
            array(new DiscoveredUri('http://example.com/foo', 0), false),
            array(new DiscoveredUri('http://example.com/bar', 0), true),
        );
    }
}

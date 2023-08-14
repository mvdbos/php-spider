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

use VDB\Spider\Filter\Prefetch\ExtractRobotsTxtException;
use VDB\Spider\Filter\Prefetch\FetchRobotsTxtException;
use VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Http;
use VDB\Uri\UriInterface;

/**
 *
 */
class RobotsTxtDisallowFilterTest extends TestCase
{
    /**
     * @covers       \VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter
     */
    public function testNoRobotsTxt()
    {
        $bogusDomain = "http://bar/baz";
        $this->expectException(FetchRobotsTxtException::class);
        new RobotsTxtDisallowFilter($bogusDomain);
    }

    /**
     * @covers       \VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter
     */
    public function testUnsupportedUrlScheme()
    {
        $unsupported = "ftp://example.com";
        $this->expectException(ExtractRobotsTxtException::class);
        new RobotsTxtDisallowFilter($unsupported);
    }


    /**
     * @covers       \VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter
     * @dataProvider userAgentMatchURIProvider
     */
    public function testUserAgentMatch(UriInterface $href, bool $expected)
    {
        $robotsTxtFilter = new RobotsTxtDisallowFilter(seedUrl: "file://" . __DIR__, userAgent: 'PHP-Spider');
        $this->assertEquals($expected, $robotsTxtFilter->match($href));
    }

    /**
     * @covers       \VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter
     * @dataProvider noUserAgentMatchURIProvider
     */
    public function testNoUserAgentMatch(UriInterface $href, bool $expected)
    {
        $robotsTxtFilter = new RobotsTxtDisallowFilter(seedUrl: "file://" . __DIR__);
        $this->assertEquals($expected, $robotsTxtFilter->match($href));
    }

    public function noUserAgentMatchURIProvider(): array
    {
        return array(
            array(new Http('http://example.com'), false),
            array(new Http('http://example.com/foo'), true),
            array(new Http('http://example.com/bar'), false),
        );
    }

    public function userAgentMatchURIProvider(): array
    {
        return array(
            array(new Http('http://example.com'), false),
            array(new Http('http://example.com/foo'), false),
            array(new Http('http://example.com/bar'), true),
        );
    }
}

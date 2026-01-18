<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Filter\Postfetch;

use VDB\Spider\Filter\Postfetch\ExternalRedirectFilter;
use VDB\Spider\Tests\Helpers\ResourceBuilder;
use VDB\Spider\Tests\TestCase;

class ExternalRedirectFilterTest extends TestCase
{
    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testNoRedirect()
    {
        // No redirect - should not be filtered
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testRedirectToSameHost()
    {
        // Redirect to same host - should not be filtered
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://example.com/newpage'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testRedirectToExternalHost()
    {
        // Redirect to external host - should be filtered
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://external.com/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testRedirectToSubdomainNotAllowed()
    {
        // Redirect to subdomain - should be filtered when allowSubDomains is false
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://www.example.com/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter(false);
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testRedirectToSubdomainAllowed()
    {
        // Redirect to subdomain - should not be filtered when allowSubDomains is true
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://www.example.com/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter(true);
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testRedirectFromSubdomainToMainDomain()
    {
        // Redirect from subdomain to main domain - allowed with allowSubDomains
        $resource = ResourceBuilder::create()
            ->withUri('http://www.example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://example.com/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter(true);
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testMultipleRedirectsToExternalHost()
    {
        // Multiple redirects ending at external host - should be filtered
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', [
                'http://example.com/redirect1',
                'http://example.com/redirect2',
                'http://external.com/final'
            ])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testMultipleRedirectsToSameHost()
    {
        // Multiple redirects staying on same host - should not be filtered
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', [
                'http://example.com/redirect1',
                'http://example.com/redirect2',
                'http://example.com/final'
            ])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testCaseInsensitiveHostComparison()
    {
        // Host comparison should be case-insensitive
        $resource = ResourceBuilder::create()
            ->withUri('http://Example.COM/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://EXAMPLE.com/newpage'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testExternalRedirectWithSubdomainsEnabled()
    {
        // External redirect should still be filtered even with allowSubDomains enabled
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://completely-different.org/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter(true);
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testRedirectToInvalidUrlWithNoHost()
    {
        // Redirect to URL without a host should not be filtered (can't determine host)
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['/relative/path'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testOriginalUriWithNoHost()
    {
        // When original URI has no host, should not be filtered (can't determine host)
        $resource = ResourceBuilder::create()
            ->withUri('/path/to/resource')
            ->withHeader('X-Guzzle-Redirect-History', ['http://example.com/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testLocalhostWithSubdomainsEnabled()
    {
        // Test with single-label hosts like 'localhost' when allowSubDomains is enabled
        $resource = ResourceBuilder::create()
            ->withUri('http://localhost/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://otherhost/page'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter(true);
        // Different single-label hosts should be filtered
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     */
    public function testSameLocalhostWithSubdomainsEnabled()
    {
        // Same single-label host should not be filtered (handled by exact match check)
        $resource = ResourceBuilder::create()
            ->withUri('http://localhost/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://localhost/newpage'])
            ->withBody('content')
            ->build();

        $filter = new ExternalRedirectFilter(true);
        $this->assertFalse($filter->match($resource));
    }
}

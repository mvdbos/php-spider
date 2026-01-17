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

use ErrorException;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Filter\Postfetch\ExternalRedirectFilter;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;

class ExternalRedirectFilterTest extends TestCase
{
    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testNoRedirect()
    {
        // No redirect - should not be filtered
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testRedirectToSameHost()
    {
        // Redirect to same host - should not be filtered
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://example.com/newpage']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testRedirectToExternalHost()
    {
        // Redirect to external host - should be filtered
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://external.com/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testRedirectToSubdomainNotAllowed()
    {
        // Redirect to subdomain - should be filtered when allowSubDomains is false
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://www.example.com/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter(false);
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testRedirectToSubdomainAllowed()
    {
        // Redirect to subdomain - should not be filtered when allowSubDomains is true
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://www.example.com/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter(true);
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testRedirectFromSubdomainToMainDomain()
    {
        // Redirect from subdomain to main domain - allowed with allowSubDomains
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://www.example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://example.com/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter(true);
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testMultipleRedirectsToExternalHost()
    {
        // Multiple redirects ending at external host - should be filtered
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => [
                    'http://example.com/redirect1',
                    'http://example.com/redirect2',
                    'http://external.com/final'
                ]
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testMultipleRedirectsToSameHost()
    {
        // Multiple redirects staying on same host - should not be filtered
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => [
                    'http://example.com/redirect1',
                    'http://example.com/redirect2',
                    'http://example.com/final'
                ]
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testCaseInsensitiveHostComparison()
    {
        // Host comparison should be case-insensitive
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://Example.COM/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://EXAMPLE.com/newpage']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testExternalRedirectWithSubdomainsEnabled()
    {
        // External redirect should still be filtered even with allowSubDomains enabled
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://completely-different.org/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter(true);
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testRedirectToInvalidUrlWithNoHost()
    {
        // Redirect to URL without a host should not be filtered (can't determine host)
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://example.com/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['/relative/path']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testOriginalUriWithNoHost()
    {
        // When original URI has no host, should not be filtered (can't determine host)
        $resource = new Resource(
            new DiscoveredUri(new Uri('/path/to/resource'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://example.com/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter();
        $this->assertFalse($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testLocalhostWithSubdomainsEnabled()
    {
        // Test with single-label hosts like 'localhost' when allowSubDomains is enabled
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://localhost/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://otherhost/page']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter(true);
        // Different single-label hosts should be filtered
        $this->assertTrue($filter->match($resource));
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\ExternalRedirectFilter
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testSameLocalhostWithSubdomainsEnabled()
    {
        // Same single-label host should not be filtered (handled by exact match check)
        $resource = new Resource(
            new DiscoveredUri(new Uri('http://localhost/page'), 0),
            new Response(200, [
                'X-Guzzle-Redirect-History' => ['http://localhost/newpage']
            ], 'content')
        );

        $filter = new ExternalRedirectFilter(true);
        $this->assertFalse($filter->match($resource));
    }
}

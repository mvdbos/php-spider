<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Downloader;

use ErrorException;
use Exception;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;

/**
 *
 */
class DownloaderTest extends TestCase
{
    private Downloader $downloader;
    protected Resource $resource;
    protected string $html;

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function setUp(): void
    {
        $this->html = file_get_contents(__DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html');
        $this->resource = new Resource(
            new DiscoveredUri(new Uri('/domains/special', 'http://example.org'), 0),
            new Response(200, [], $this->html)
        );

        $this->downloader = new Downloader();

        $requestHandler = $this->getMockBuilder('VDB\Spider\RequestHandler\RequestHandlerInterface')->getMock();
        $requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->returnValue($this->resource));

        $this->downloader->setRequestHandler($requestHandler);
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     */
    public function testDefaultRequestHandler()
    {
        $this->assertInstanceOf(
            '\VDB\Spider\RequestHandler\GuzzleRequestHandler',
            (new Downloader())->getRequestHandler()
        );
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     *
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testDownload()
    {
        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));
        $this->assertInstanceOf('VDB\\Spider\\Resource', $resource);
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     *
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testDownloadFailed()
    {
        $requestHandler = $this->getMockBuilder('VDB\Spider\RequestHandler\RequestHandlerInterface')->getMock();
        $requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->throwException(new Exception));
        $this->downloader->setRequestHandler($requestHandler);

        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertFalse($resource);
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     *
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testFilterNotMatches()
    {
        $filterNeverMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterNeverMatch
            ->expects($this->any())
            ->method('match')
            ->will($this->returnValue(false));
        $this->downloader->addPostFetchFilter($filterNeverMatch);

        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertInstanceOf('VDB\\Spider\\Resource', $resource);
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     *
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testDownloadLimit()
    {
        $this->downloader->setDownloadLimit(1);
        $this->downloader->download(new DiscoveredUri('http://foobar.org', 0));
        $this->assertTrue($this->downloader->isDownLoadLimitExceeded());
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     */
    public function testFilterMatches()
    {
        $filterAlwaysMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterAlwaysMatch
            ->expects($this->any())
            ->method('match')
            ->will($this->returnValue(true));
        $downloader = new Downloader(null, null, [$filterAlwaysMatch]);

        $resource = $downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertFalse($resource);
    }
}

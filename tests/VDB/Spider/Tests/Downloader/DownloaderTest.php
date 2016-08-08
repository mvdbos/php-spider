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

use GuzzleHttp\Psr7\Response;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Downloader\DownloaderInterface;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Resource;
use VDB\Uri\Uri;

/**
 *
 */
class DownloaderTest extends TestCase
{
    /**
     * @var Downloader
     */
    private $downloader;

    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $html;

    public function setUp()
    {
        $this->html = file_get_contents(__DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html');
        $this->resource = new Resource(
            new DiscoveredUri(new Uri('/domains/special', 'http://example.org')),
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
     * @covers VDB\Spider\Downloader\Downloader
     */
    public function testDownload()
    {
        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org')));
        $this->assertInstanceOf('VDB\\Spider\\Resource', $resource);
    }

    /**
     * @covers VDB\Spider\Downloader\Downloader
     */
    public function testDownloadFailed()
    {
        $requestHandler = $this->getMockBuilder('VDB\Spider\RequestHandler\RequestHandlerInterface')->getMock();
        $requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->throwException(new \Exception));
        $this->downloader->setRequestHandler($requestHandler);

        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org')));

        $this->assertFalse($resource);
    }

    /**
     * @covers VDB\Spider\Downloader\Downloader
     */
    public function testFilterNotMatches()
    {
        $filterNeverMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterNeverMatch
            ->expects($this->any())
            ->method('match')
            ->will($this->returnValue(false));
        $this->downloader->addPostFetchFilter($filterNeverMatch);

        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org')));

        $this->assertInstanceOf('VDB\\Spider\\Resource', $resource);
    }

    /**
     * @covers VDB\Spider\Downloader\Downloader
     */
    public function testFilterMatches()
    {
        $filterAlwaysMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterAlwaysMatch
            ->expects($this->any())
            ->method('match')
            ->will($this->returnValue(true));
        $this->downloader->addPostFetchFilter($filterAlwaysMatch);

        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org')));

        $this->assertFalse($resource);
    }
}

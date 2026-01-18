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
use ReflectionClass;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\Resource;
use VDB\Spider\Tests\Helpers\ResourceBuilder;
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
        $this->resource = ResourceBuilder::create()
            ->withUri('http://example.org/domains/special')
            ->withBody($this->html)
            ->build();

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
     * @covers \VDB\Spider\Downloader\Downloader::__construct
     * @covers \VDB\Spider\Downloader\Downloader::getRequestHandler
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
     * @covers \VDB\Spider\Downloader\Downloader::download
     * @covers \VDB\Spider\Downloader\Downloader::fetchResource
     * @covers \VDB\Spider\Downloader\Downloader::dispatch
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
     * @covers \VDB\Spider\Downloader\Downloader::download
     * @covers \VDB\Spider\Downloader\Downloader::fetchResource
     * @covers \VDB\Spider\Downloader\Downloader::dispatch
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
     * @covers \VDB\Spider\Downloader\Downloader::addPostFetchFilter
     * @covers \VDB\Spider\Downloader\Downloader::download
     * @covers \VDB\Spider\Downloader\Downloader::matchesPostfetchFilter
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
     * @covers \VDB\Spider\Downloader\Downloader::setDownloadLimit
     * @covers \VDB\Spider\Downloader\Downloader::download
     * @covers \VDB\Spider\Downloader\Downloader::isDownLoadLimitExceeded
     */
    public function testDownloadLimit()
    {
        $this->downloader->setDownloadLimit(1);
        $this->downloader->download(new DiscoveredUri('http://foobar.org', 0));
        $this->assertTrue($this->downloader->isDownLoadLimitExceeded());
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     * @covers \VDB\Spider\Downloader\Downloader::__construct
     * @covers \VDB\Spider\Downloader\Downloader::download
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

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     * @covers \VDB\Spider\Downloader\Downloader::setDownloadLimit
     * @covers \VDB\Spider\Downloader\Downloader::download
     * @covers \VDB\Spider\Downloader\Downloader::isDownLoadLimitExceeded
     */
    public function testGetDownloadLimit()
    {
        $this->downloader->setDownloadLimit(10);
        $this->assertEquals(10, $this->downloader->getDownloadLimit());
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     * @covers \VDB\Spider\Downloader\Downloader::setPersistenceHandler
     * @covers \VDB\Spider\Downloader\Downloader::getPersistenceHandler
     */
    public function testSetAndGetPersistenceHandler()
    {
        $handler = $this->getMockBuilder('VDB\Spider\PersistenceHandler\PersistenceHandlerInterface')->getMock();
        $this->downloader->setPersistenceHandler($handler);
        $this->assertSame($handler, $this->downloader->getPersistenceHandler());
    }

    /**
     * @covers \VDB\Spider\Downloader\Downloader
     * @covers \VDB\Spider\Downloader\Downloader::setRequestHandler
     * @covers \VDB\Spider\Downloader\Downloader::getRequestHandler
     */
    public function testSetAndGetRequestHandler()
    {
        $handler = $this->getMockBuilder('VDB\Spider\RequestHandler\RequestHandlerInterface')->getMock();
        $this->downloader->setRequestHandler($handler);
        $this->assertSame($handler, $this->downloader->getRequestHandler());
    }
    /**
     * @covers \VDB\Spider\Downloader\Downloader::download
     * @covers \VDB\Spider\Downloader\Downloader::matchesPostfetchFilter
     * @covers \VDB\Spider\Downloader\Downloader::dispatch
     * @covers \VDB\Spider\Downloader\Downloader::fetchResource
     */
    public function testFilterMatchesWithDispatchEvent()
    {
        // Test that when a postfetch filter matches, it dispatches the event and returns false
        $filterAlwaysMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterAlwaysMatch
            ->expects($this->once())
            ->method('match')
            ->will($this->returnValue(true));
        
        // Create a mock event dispatcher to verify the event is dispatched
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $dispatcher
            ->expects($this->atLeastOnce()) // pre-request, post-request, and postfetch events
            ->method('dispatch');
        
        $downloader = new Downloader();
        $downloader->setRequestHandler($this->downloader->getRequestHandler());
        
        // Use reflection to inject mock dispatcher
        $reflection = new ReflectionClass($downloader);
        $property = $reflection->getProperty('dispatcher');
        $property->setAccessible(true);
        $property->setValue($downloader, $dispatcher);
        
        $downloader->addPostFetchFilter($filterAlwaysMatch);

        $resource = $downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertFalse($resource);
    }
}

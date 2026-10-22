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

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Exception;
use ReflectionClass;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\Resource;
use VDB\Spider\Tests\Helpers\ResourceBuilder;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

#[CoversClass(\VDB\Spider\Downloader\Downloader::class)]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, '__construct')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'getRequestHandler')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'download')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'fetchResource')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'dispatch')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'addPostFetchFilter')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'matchesPostfetchFilter')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'setDownloadLimit')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'isDownLoadLimitExceeded')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'setPersistenceHandler')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'getPersistenceHandler')]
#[CoversMethod(\VDB\Spider\Downloader\Downloader::class, 'setRequestHandler')]
class DownloaderTest extends TestCase
{
    private Downloader $downloader;
    protected Resource $resource;
    protected string $html;

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
            ->willReturn($this->resource);

        $this->downloader->setRequestHandler($requestHandler);
    }

    public function testDefaultRequestHandler()
    {
        $this->assertInstanceOf(
            '\VDB\Spider\RequestHandler\GuzzleRequestHandler',
            (new Downloader())->getRequestHandler()
        );
    }

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testDownload()
    {
        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));
        $this->assertInstanceOf('VDB\\Spider\\Resource', $resource);
    }

    /**
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
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testFilterNotMatches()
    {
        $filterNeverMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterNeverMatch
            ->expects($this->any())
            ->method('match')
            ->willReturn(false);
        $this->downloader->addPostFetchFilter($filterNeverMatch);

        $resource = $this->downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertInstanceOf('VDB\\Spider\\Resource', $resource);
    }

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    public function testDownloadLimit()
    {
        $this->downloader->setDownloadLimit(1);
        $this->downloader->download(new DiscoveredUri('http://foobar.org', 0));
        $this->assertTrue($this->downloader->isDownLoadLimitExceeded());
    }

    public function testFilterMatches()
    {
        $filterAlwaysMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterAlwaysMatch
            ->expects($this->any())
            ->method('match')
            ->willReturn(true);
        $downloader = new Downloader(null, null, [$filterAlwaysMatch]);

        $resource = $downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertFalse($resource);
    }

    public function testGetDownloadLimit()
    {
        $this->downloader->setDownloadLimit(10);
        $this->assertEquals(10, $this->downloader->getDownloadLimit());
    }

    public function testSetAndGetPersistenceHandler()
    {
        $handler = $this->getMockBuilder('VDB\Spider\PersistenceHandler\PersistenceHandlerInterface')->getMock();
        $this->downloader->setPersistenceHandler($handler);
        $this->assertSame($handler, $this->downloader->getPersistenceHandler());
    }

    public function testSetAndGetRequestHandler()
    {
        $handler = $this->getMockBuilder('VDB\Spider\RequestHandler\RequestHandlerInterface')->getMock();
        $this->downloader->setRequestHandler($handler);
        $this->assertSame($handler, $this->downloader->getRequestHandler());
    }
    public function testFilterMatchesWithDispatchEvent()
    {
        // Test that when a postfetch filter matches, it dispatches the event and returns false
        $filterAlwaysMatch = $this->getMockBuilder('VDB\Spider\Filter\PostFetchFilterInterface')->getMock();
        $filterAlwaysMatch
            ->expects($this->once())
            ->method('match')
            ->willReturn(true);
        
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
        $property->setValue($downloader, $dispatcher);
        
        $downloader->addPostFetchFilter($filterAlwaysMatch);

        $resource = $downloader->download(new DiscoveredUri(new Uri('http://foobar.org'), 0));

        $this->assertFalse($resource);
    }
}

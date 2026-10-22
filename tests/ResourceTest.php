<?php
namespace VDB\Spider\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Spider\Tests\Helpers\ResourceBuilder;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

#[CoversClass(\VDB\Spider\Resource::class)]
#[CoversMethod(\VDB\Spider\Resource::class, 'getEffectiveUri')]
class ResourceTest extends TestCase
{
    /**
     * @var Resource
     */
    protected Resource $resource;

    /**
     * @var string
     */
    protected $html;

    protected function setUp(): void
    {
        $this->html = file_get_contents(__DIR__ . '/Fixtures/ResourceTestHTMLResource.html');
        $this->resource = ResourceBuilder::create()
            ->withUri('http://example.org/domains/special')
            ->withBody($this->html)
            ->build();
    }

    public function testGetCrawler()
    {
        $this->assertInstanceOf('Symfony\\Component\\DomCrawler\\Crawler', $this->resource->getCrawler());
    }

    public function testGetUri()
    {
        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $this->resource->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->resource->getUri()->toString());
    }

    public function testGetResponse()
    {
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $this->resource->getResponse());
        $this->assertEquals($this->html, $this->resource->getResponse()->getBody()->__toString());
    }

    public function testSerialization()
    {
        $serialized = serialize($this->resource);
        $deserialized = unserialize($serialized);

        $this->assertInstanceOf('VDB\\Spider\\Resource', $deserialized);
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $deserialized->getResponse());
        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $deserialized->getUri());
        $this->assertEquals($this->resource->getUri()->__toString(), $deserialized->getUri()->__toString());
        $this->assertEquals($this->html, $deserialized->getResponse()->getBody()->__toString());
        $this->assertEquals($this->resource->getCrawler()->html(), $deserialized->getCrawler()->html());
    }

    public function testGetEffectiveUriWithoutRedirects()
    {
        // When no redirects occurred, should return the original URI
        $this->assertEquals('http://example.org/domains/special', $this->resource->getEffectiveUri());
    }

    public function testGetEffectiveUriWithRedirects()
    {
        // Simulate Guzzle redirect tracking headers
        $resource = ResourceBuilder::create()
            ->withUri('http://original.com/page')
            ->withHeaders([
                'X-Guzzle-Redirect-History' => [
                    'http://original.com/redirect1',
                    'http://final.com/destination'
                ]
            ])
            ->withBody('content')
            ->build();

        // Should return the last URI in the redirect history
        $this->assertEquals('http://final.com/destination', $resource->getEffectiveUri());
    }

    public function testGetEffectiveUriWithSingleRedirect()
    {
        // Simulate a single redirect
        $resource = ResourceBuilder::create()
            ->withUri('http://original.com/page')
            ->withHeader('X-Guzzle-Redirect-History', ['http://redirected.com/final'])
            ->withBody('content')
            ->build();

        $this->assertEquals('http://redirected.com/final', $resource->getEffectiveUri());
    }

    public function testGetEffectiveUriWithEmptyRedirectHistory()
    {
        // When redirect history header is empty, should return original URI
        $resource = ResourceBuilder::create()
            ->withUri('http://original.com/page')
            ->withHeader('X-Guzzle-Redirect-History', [])
            ->withBody('content')
            ->build();

        $this->assertEquals('http://original.com/page', $resource->getEffectiveUri());
    }
}

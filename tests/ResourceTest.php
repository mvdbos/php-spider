<?php
namespace VDB\Spider\Tests;

use ErrorException;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;

/**
 */
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

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    protected function setUp(): void
    {
        $this->html = file_get_contents(__DIR__ . '/Fixtures/ResourceTestHTMLResource.html');
        $this->resource = new Resource(
            new DiscoveredUri(new Uri('/domains/special', 'http://example.org'), 0),
            new Response(200, [], $this->html)
        );
    }

    /**
     * @covers \VDB\Spider\Resource
     */
    public function testGetCrawler()
    {
        $this->assertInstanceOf('Symfony\\Component\\DomCrawler\\Crawler', $this->resource->getCrawler());
    }

    /**
     * @covers \VDB\Spider\Resource
     */
    public function testGetUri()
    {
        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $this->resource->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->resource->getUri()->toString());
    }

    /**
     * @covers \VDB\Spider\Resource
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $this->resource->getResponse());
        $this->assertEquals($this->html, $this->resource->getResponse()->getBody()->__toString());
    }

    /**
     * @covers \VDB\Spider\Resource
     */
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
}

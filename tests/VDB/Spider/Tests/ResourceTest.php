<?php
namespace VDB\Spider\Tests;

use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Uri\Uri;
use VDB\Spider\Uri\DiscoveredUri;

/**
 */
class ResourceTest extends TestCase
{
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $html;

    protected function setUp()
    {
        $this->html = file_get_contents(__DIR__ . '/Fixtures/ResourceTestHTMLResource.html');
        $this->resource = new Resource(
            new DiscoveredUri(new Uri('/domains/special', 'http://example.org')),
            new Response(200, [], $this->html)
        );
    }

    /**
     * @covers VDB\Spider\Resource
     */
    public function testGetCrawler()
    {
        $this->assertInstanceOf('Symfony\\Component\\DomCrawler\\Crawler', $this->resource->getCrawler());
    }

    /**
     * @covers VDB\Spider\Resource
     */
    public function testGetUri()
    {
        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $this->resource->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->resource->getUri()->toString());
    }

    /**
     * @covers VDB\Spider\Resource
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $this->resource->getResponse());
        $this->assertEquals($this->html, $this->resource->getResponse()->getBody()->__toString());
    }

    /**
     * @covers VDB\Spider\Resource
     */
    public function testSerialization()
    {
        $serialized = serialize($this->resource);
        $unserialized = unserialize($serialized);

        $this->assertInstanceOf('VDB\\Spider\\Resource', $unserialized);
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $unserialized->getResponse());
        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $unserialized->getUri());
        $this->assertEquals($this->resource->getUri()->__toString(), $unserialized->getUri()->__toString());
        $this->assertEquals($this->html, $unserialized->getResponse()->getBody()->__toString());
        $this->assertEquals($this->resource->getCrawler()->html(), $unserialized->getCrawler()->html());
    }
}

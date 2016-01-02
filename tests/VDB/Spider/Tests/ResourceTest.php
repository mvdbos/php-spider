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

    protected function setUp()
    {
        $html = file_get_contents(__DIR__ . '/Fixtures/ResourceTestHTMLResource.html');
        $this->resource = new Resource(
            new DiscoveredUri(new Uri('/domains/special', 'http://example.org')),
            new Response(200, [], $html)
        );
    }

    /**
     * @covers VDB\Spider\Resource::getCrawler
     */
    public function testGetCrawler()
    {
        $this->assertInstanceOf('Symfony\\Component\\DomCrawler\\Crawler', $this->resource->getCrawler());
    }

    /**
     * @covers VDB\Spider\Resource::getUri
     */
    public function testGetUri()
    {
        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $this->resource->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->resource->getUri()->toString());
    }

    /**
     * @covers VDB\Spider\Resource::getResponse
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('Psr\\Http\\Message\\ResponseInterface', $this->resource->getResponse());
    }
}

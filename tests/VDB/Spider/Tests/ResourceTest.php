<?php
namespace VDB\Spider\Tests;

use Guzzle\Http\Message\Response;
use VDB\Spider\Resource;
use VDB\Uri\Uri;

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
            new Uri('/domains/special', 'http://example.org'),
            new Response(200, null, $html)
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
     * @covers VDB\Spider\Resource::getLink
     */
    public function testGetLink()
    {
        $this->assertInstanceOf('VDB\\URI\Uri', $this->resource->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->resource->getUri()->toString());
    }

    /**
     * @covers VDB\Spider\Resource::getResponse
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('Guzzle\\Http\\Message\\Response', $this->resource->getResponse());
    }

    /**
     * @covers VDB\Spider\Resource::setFiltered
     * @covers VDB\Spider\Resource::isFiltered
     * @covers VDB\Spider\Resource::getFilterReason
     */
    public function testSetFiltered()
    {
        $this->resource->setFiltered(true, 'goodReason');
        $this->assertTrue($this->resource->isFiltered());
        $this->assertEquals('goodReason', $this->resource->getFilterReason());
    }

    /**
     * @covers VDB\Spider\Resource::getIdentifier
     */
    public function testGetIdentifier()
    {
        $this->assertEquals('http://example.org/domains/special', $this->resource->getIdentifier());
    }
}

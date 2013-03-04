<?php
namespace VDB\Spider\Tests;

use VDB\Spider\Resource;
use VDB\URI\GenericURI;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Response;

/**
 */
class ResourceTest extends TestCase
{
    /**
     * @var Resource
     */
    protected $document;

    protected function setUp()
    {
        $html = file_get_contents(__DIR__ . '/Fixtures/ResourceTestHTMLResource.html');
        $this->document = new Resource(
            new GenericURI('/domains/special', 'http://example.org'),
            new Response(),
            new Crawler($html, 'http://example.org')
        );
    }

    /**
     * @covers VDB\Spider\Resource::getCrawler
     */
    public function testGetCrawler()
    {
        $this->assertInstanceOf('Symfony\\Component\\DomCrawler\\Crawler', $this->document->getCrawler());
    }

    /**
     * @covers VDB\Spider\Resource::getLink
     */
    public function testGetLink()
    {
        $this->assertInstanceOf('VDB\\URI\GenericURI', $this->document->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->document->getUri()->recompose());
    }

    /**
     * @covers VDB\Spider\Resource::getResponse
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('Symfony\\Component\\BrowserKit\\Response', $this->document->getResponse());
    }

    /**
     * @covers VDB\Spider\Resource::setFiltered
     * @covers VDB\Spider\Resource::isFiltered
     * @covers VDB\Spider\Resource::getFilterReason
     */
    public function testSetFiltered()
    {
        $this->document->setFiltered(true, 'goodReason');
        $this->assertTrue($this->document->isFiltered());
        $this->assertEquals('goodReason', $this->document->getFilterReason());
    }

    /**
     * @covers VDB\Spider\Resource::getIdentifier
     */
    public function testGetIdentifier()
    {
        $this->assertEquals('http://example.org/domains/special', $this->document->getIdentifier());
    }
}

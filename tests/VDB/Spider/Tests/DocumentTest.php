<?php
namespace VDB\Spider\Tests;

use VDB\Spider\Document;
use VDB\URI\GenericURI;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Response;

/**
 */
class DocumentTest extends TestCase
{
    /**
     * @var Document
     */
    protected $document;

    protected function setUp()
    {
        $html = file_get_contents(__DIR__ . '/Fixtures/DocumentTestHTMLDocument.html');
        $this->document = new Document(
            new GenericURI('/domains/special', 'http://example.org'),
            new Response(),
            new Crawler($html, 'http://example.org')
        );
    }

    /**
     * @covers VDB\Spider\Document::getCrawler
     */
    public function testGetCrawler()
    {
        $this->assertInstanceOf('Symfony\\Component\\DomCrawler\\Crawler', $this->document->getCrawler());
    }

    /**
     * @covers VDB\Spider\Document::getLink
     */
    public function testGetLink()
    {
        $this->assertInstanceOf('VDB\\URI\GenericURI', $this->document->getUri());
        $this->assertEquals('http://example.org/domains/special', $this->document->getUri()->recompose());
    }

    /**
     * @covers VDB\Spider\Document::getResponse
     */
    public function testGetResponse()
    {
        $this->assertInstanceOf('Symfony\\Component\\BrowserKit\\Response', $this->document->getResponse());
    }

    /**
     * @covers VDB\Spider\Document::setFiltered
     * @covers VDB\Spider\Document::isFiltered
     * @covers VDB\Spider\Document::getFilterReason
     */
    public function testSetFiltered()
    {
        $this->document->setFiltered(true, 'goodReason');
        $this->assertTrue($this->document->isFiltered());
        $this->assertEquals('goodReason', $this->document->getFilterReason());
    }

    /**
     * @covers VDB\Spider\Document::getIdentifier
     */
    public function testGetIdentifier()
    {
        $this->assertEquals('http://example.org/domains/special', $this->document->getIdentifier());
    }
}

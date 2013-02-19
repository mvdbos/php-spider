<?php
namespace VDB\Spider;

use VDB\Spider\Tests\TestCase;
use VDB\URI\GenericURI;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Filter\Prefetch\AlreadyVisitedFilter;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use \PHPUnit_Framework_MockObject_MockObject;

use Pimple;

/**
 */
class SpiderTest extends TestCase
{
    /**
     * @var Spider
     */
    protected $spider;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestHandler;

    /** @var GenericURI */
    protected $link1;
    /** @var GenericURI */
    protected $link2;
    /** @var GenericURI */
    protected $link3;
    /** @var GenericURI */
    protected $link4;
    /** @var GenericURI */
    protected $link5;

    /** @var Crawler */
    protected $crawler1;
    /** @var Crawler */
    protected $crawler2;
    /** @var Crawler */
    protected $crawler3;
    /** @var Crawler */
    protected $crawler4;
    /** @var Crawler */
    protected $crawler5;

    /** @var string */
    protected $href1;
    protected $href2;
    protected $href3;
    protected $href4;
    protected $href5;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $container = new Pimple();
        $this->spider = new Spider($container);

        $this->requestHandler = $this->getMock('VDB\Spider\RequestHandler\RequestHandler');

        $this->href1 = 'http://php-spider.org/1';
        $this->href2 = 'http://php-spider.org/2';
        $this->href3 = 'http://php-spider.org/3';
        $this->href4 = 'http://php-spider.org/4';
        $this->href5 = 'http://php-spider.org/5';

        $this->link1 = new GenericURI($this->href1);
        $this->link2 = new GenericURI($this->href2);
        $this->link3 = new GenericURI($this->href3);
        $this->link4 = new GenericURI($this->href4);
        $this->link5 = new GenericURI($this->href5);
        
        $html1 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocument1.html');
        $this->crawler1 = new Crawler($html1, $this->href1);

        $html2 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocument2.html');
        $this->crawler2 = new Crawler($html2, $this->href2);

        $html3 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocument3.html');
        $this->crawler3 = new Crawler($html3, $this->href3);

        $html4 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocument4.html');
        $this->crawler4 = new Crawler($html4, $this->href4);

        $html5 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocument5.html');
        $this->crawler5 = new Crawler($html5, $this->href5);

        $this->requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(array($this, 'doTestRequest')));

        $this->spider->setRequestHandler($this->requestHandler);
        $this->spider->addDiscoverer(new XPathExpressionDiscoverer('//a'));

    }

    /**
     * @return Document
     */
    public function doTestRequest()
    {
        $link = func_get_arg(0);

        switch ($link->recompose()) {
            case $this->link1->recompose():
                return $this->getDocument($this->link1, $this->crawler1);
            case $this->link2->recompose():
                return $this->getDocument($this->link2, $this->crawler2);
            case $this->link3->recompose():
                return $this->getDocument($this->link3, $this->crawler3);
            case $this->link4->recompose():
                return $this->getDocument($this->link4, $this->crawler4);
            case $this->link5->recompose():
                return $this->getDocument($this->link5, $this->crawler5);
        }
    }


    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlAlreadyVisitedFilter()
    {
        $this->spider->addPreFetchFilter(new AlreadyVisitedFilter('http://php-spider.org/1'));

        $report = $this->spider->crawl('http://php-spider.org/1');

        $this->assertCount(1, $report['filtered']);
        $this->assertCount(5, $report['queued']);
        $this->assertCount(0, $report['failed']);
        $this->assertEquals('Already Visited', $report['filtered']['http://php-spider.org/1']);
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlMaxDepthOne()
    {
        $this->spider->setMaxDepth(1);

        $report = $this->spider->crawl('http://php-spider.org/1');

        $this->assertCount(0, $report['filtered']);
        $this->assertCount(4, $report['queued']);
        $this->assertCount(0, $report['failed']);
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlMaxQueueSize()
    {
        $this->spider->setMaxDepth(40);
        $this->spider->setMaxQueueSize(10);

        $report = $this->spider->crawl('http://php-spider.org/1');

        $this->assertCount(0, $report['filtered'], 'Filtered count');
        $this->assertCount(10, $report['queued'], 'Queued count');
        $this->assertCount(1, $report['failed'], 'Failed count');
    }


    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlFailedRequest()
    {
        $this->requestHandler
            ->expects($this->any())
            ->method('request')
            ->will(
                $this->throwException(new Exception('Failed mock request!'))
            );

        $report = $this->spider->crawl('http://php-spider.org/1');

        $this->assertCount(0, $report['filtered'], 'Filtered count');
        $this->assertCount(0, $report['queued'], 'Queued count');
        $this->assertCount(1, $report['failed'], 'Failed count');
    }


    /**
     * @covers VDB\Spider\Spider::process
     */
    public function testProcess()
    {
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }
}

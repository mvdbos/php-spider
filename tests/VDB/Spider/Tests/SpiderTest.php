<?php
namespace VDB\Spider;

use VDB\Spider\Tests\TestCase;
use VDB\Spider\Tests\Fixtures\TitleExtractorProcessor;
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
    /** @var GenericURI */
    protected $link6;
    /** @var GenericURI */
    protected $link7;

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
    /** @var Crawler */
    protected $crawler6;
    /** @var Crawler */
    protected $crawler7;

    /** @var string */
    protected $href1;
    protected $href2;
    protected $href3;
    protected $href4;
    protected $href5;
    protected $href6;
    protected $href7;

    /** @var TitleExtractorProcessor */
    protected $titleExtractorProcessor;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $container = new Pimple();
        $this->spider = new Spider($container);

        $this->requestHandler = $this->getMock('VDB\Spider\RequestHandler\RequestHandler');

        $this->href1 = 'http://php-spider.org/A';
        $this->href2 = 'http://php-spider.org/B';
        $this->href3 = 'http://php-spider.org/C';
        $this->href4 = 'http://php-spider.org/D';
        $this->href5 = 'http://php-spider.org/E';
        $this->href6 = 'http://php-spider.org/F';
        $this->href7 = 'http://php-spider.org/G';

        $this->link1 = new GenericURI($this->href1);
        $this->link2 = new GenericURI($this->href2);
        $this->link3 = new GenericURI($this->href3);
        $this->link4 = new GenericURI($this->href4);
        $this->link5 = new GenericURI($this->href5);
        $this->link6 = new GenericURI($this->href6);
        $this->link7 = new GenericURI($this->href7);

        $html1 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentA.html');
        $this->crawler1 = new Crawler($html1, $this->href1);

        $html2 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentB.html');
        $this->crawler2 = new Crawler($html2, $this->href2);

        $html3 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentC.html');
        $this->crawler3 = new Crawler($html3, $this->href3);

        $html4 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentD.html');
        $this->crawler4 = new Crawler($html4, $this->href4);

        $html5 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentE.html');
        $this->crawler5 = new Crawler($html5, $this->href5);

        $html6 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentF.html');
        $this->crawler6 = new Crawler($html6, $this->href6);

        $html7 = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLDocumentG.html');
        $this->crawler7 = new Crawler($html7, $this->href7);

        $this->requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(array($this, 'doTestRequest')));

        $this->spider->setRequestHandler($this->requestHandler);
        $this->spider->addDiscoverer(new XPathExpressionDiscoverer('//a'));

        $this->titleExtractorProcessor = new TitleExtractorProcessor();
        $this->spider->addProcessor($this->titleExtractorProcessor);
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
            case $this->link6->recompose():
                return $this->getDocument($this->link6, $this->crawler6);
            case $this->link7->recompose():
                return $this->getDocument($this->link7, $this->crawler7);
        }
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     * Behaviour as explained here: https://en.wikipedia.org/wiki/Depth-first_search#Example
     */
    public function testCrawlDFSDefaultBehaviour()
    {
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(100);

        $this->spider->crawl('http://php-spider.org/A');
        $this->spider->process();

        $this->assertEquals('AEFCGBD', $this->titleExtractorProcessor->titles);
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     */
    public function testCrawlBFSDefaultBehaviour()
    {
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(100);

        $this->spider->crawl('http://php-spider.org/A');
        $this->spider->process();

        $this->assertEquals('ABCEDFG', $this->titleExtractorProcessor->titles);
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     * Behaviour as explained here: https://en.wikipedia.org/wiki/Depth-first_search#Example
     */
    public function testCrawlDFSMaxDepthOne()
    {
        $this->spider->setMaxDepth(1);

        $this->spider->crawl('http://php-spider.org/A');
        $this->spider->process();
        $this->assertEquals('AECB', $this->titleExtractorProcessor->titles);
    }

    public function testCrawlBFSMaxDepthOne()
    {
        $this->spider->setMaxDepth(1);
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);

        $this->spider->crawl('http://php-spider.org/A');
        $this->spider->process();
        $this->assertEquals('ABCE', $this->titleExtractorProcessor->titles);
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlDFSMaxQueueSize()
    {
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(3);

        $this->spider->crawl('http://php-spider.org/A');
        $this->spider->process();
        $this->assertEquals('AEF', $this->titleExtractorProcessor->titles);
    }

    public function testCrawlBFSMaxQueueSize()
    {
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(3);

        $this->spider->crawl('http://php-spider.org/A');
        $this->spider->process();
        $this->assertEquals('ABC', $this->titleExtractorProcessor->titles);
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
}

<?php
namespace VDB\Spider;

use VDB\Spider\Tests\TestCase;
use VDB\Spider\Tests\Fixtures\TitleExtractorProcessor;
use VDB\URI\GenericURI;
use Exception;
use Symfony\Component\DomCrawler\Crawler;
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
    protected $linkA;
    /** @var GenericURI */
    protected $linkB;
    /** @var GenericURI */
    protected $linkC;
    /** @var GenericURI */
    protected $linkD;
    /** @var GenericURI */
    protected $linkE;
    /** @var GenericURI */
    protected $linkF;
    /** @var GenericURI */
    protected $linkG;

    /** @var Crawler */
    protected $crawlerA;
    /** @var Crawler */
    protected $crawlerB;
    /** @var Crawler */
    protected $crawlerC;
    /** @var Crawler */
    protected $crawlerD;
    /** @var Crawler */
    protected $crawlerE;
    /** @var Crawler */
    protected $crawlerF;
    /** @var Crawler */
    protected $crawlerG;

    /** @var string */
    protected $hrefA;
    protected $hrefB;
    protected $hrefC;
    protected $hrefD;
    protected $hrefE;
    protected $hrefF;
    protected $hrefG;

    /** @var TitleExtractorProcessor */
    protected $titleExtractorProcessor;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->spider = new Spider('http://php-spider.org/A');

        $this->requestHandler = $this->getMock('VDB\Spider\RequestHandler\RequestHandler');

        $this->hrefA = 'http://php-spider.org/A';
        $this->hrefB = 'http://php-spider.org/B';
        $this->hrefC = 'http://php-spider.org/C';
        $this->hrefD = 'http://php-spider.org/D';
        $this->hrefE = 'http://php-spider.org/E';
        $this->hrefF = 'http://php-spider.org/F';
        $this->hrefG = 'http://php-spider.org/G';

        $this->linkA = new GenericURI($this->hrefA);
        $this->linkB = new GenericURI($this->hrefB);
        $this->linkC = new GenericURI($this->hrefC);
        $this->linkD = new GenericURI($this->hrefD);
        $this->linkE = new GenericURI($this->hrefE);
        $this->linkF = new GenericURI($this->hrefF);
        $this->linkG = new GenericURI($this->hrefG);

        $htmlA = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceA.html');
        $this->crawlerA = new Crawler($htmlA, $this->hrefA);

        $htmlB = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceB.html');
        $this->crawlerB = new Crawler($htmlB, $this->hrefB);

        $htmlC = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceC.html');
        $this->crawlerC = new Crawler($htmlC, $this->hrefC);

        $htmlD = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceD.html');
        $this->crawlerD = new Crawler($htmlD, $this->hrefD);

        $htmlE = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceE.html');
        $this->crawlerE = new Crawler($htmlE, $this->hrefE);

        $htmlF = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceF.html');
        $this->crawlerF = new Crawler($htmlF, $this->hrefF);

        $htmlG = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceG.html');
        $this->crawlerG = new Crawler($htmlG, $this->hrefG);

        $this->requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(array($this, 'doTestRequest')));

        $this->spider->setRequestHandler($this->requestHandler);
        $this->spider->addDiscoverer(new XPathExpressionDiscoverer('//a'));
    }

    /**
     * @return Resource
     */
    public function doTestRequest()
    {
        $link = func_get_arg(0);

        switch ($link->toString()) {
            case $this->linkA->toString():
                return $this->getResource($this->linkA, $this->crawlerA);
            case $this->linkB->toString():
                return $this->getResource($this->linkB, $this->crawlerB);
            case $this->linkC->toString():
                return $this->getResource($this->linkC, $this->crawlerC);
            case $this->linkD->toString():
                return $this->getResource($this->linkD, $this->crawlerD);
            case $this->linkE->toString():
                return $this->getResource($this->linkE, $this->crawlerE);
            case $this->linkF->toString():
                return $this->getResource($this->linkF, $this->crawlerF);
            case $this->linkG->toString():
                return $this->getResource($this->linkG, $this->crawlerG);
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

        $report = $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefE,
            $this->hrefF,
            $this->hrefC,
            $this->hrefG,
            $this->hrefB,
            $this->hrefD
        );

        foreach ($report['queued'] as $index => $uri) {
            $this->assertEquals($expected[$index], $uri);
        }
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

        $report = $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefB,
            $this->hrefC,
            $this->hrefE,
            $this->hrefD,
            $this->hrefF,
            $this->hrefG
        );

        foreach ($report['queued'] as $index => $uri) {
            $this->assertEquals($expected[$index], $uri);
        }
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     * Behaviour as explained here: https://en.wikipedia.org/wiki/Depth-first_search#Example
     */
    public function testCrawlDFSMaxDepthOne()
    {
        $this->spider->setMaxDepth(1);

        $report = $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefE,
            $this->hrefC,
            $this->hrefB,
        );

        foreach ($report['queued'] as $index => $uri) {
            $this->assertEquals($expected[$index], $uri);
        }
    }

    public function testCrawlBFSMaxDepthOne()
    {
        $this->spider->setMaxDepth(1);
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);

        $report = $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefB,
            $this->hrefC,
            $this->hrefE,
        );

        foreach ($report['queued'] as $index => $uri) {
            $this->assertEquals($expected[$index], $uri);
        }
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlDFSMaxQueueSize()
    {
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(3);

        $report = $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefE,
            $this->hrefF,
        );

        foreach ($report['queued'] as $index => $uri) {
            $this->assertEquals($expected[$index], $uri);
        }
    }

    public function testCrawlBFSMaxQueueSize()
    {
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(3);

        $report = $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefB,
            $this->hrefC,
        );

        foreach ($report['queued'] as $index => $uri) {
            $this->assertEquals($expected[$index], $uri);
        }
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

        $report = $this->spider->crawl();

        $this->assertCount(0, $report['filtered'], 'Filtered count');
        $this->assertCount(0, $report['queued'], 'Queued count');
        $this->assertCount(1, $report['failed'], 'Failed count');
    }
}

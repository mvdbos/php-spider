<?php
namespace VDB\Spider;

use Exception;
use Guzzle\Http\Message\Response;
use PHPUnit_Framework_MockObject_MockObject;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;

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

    /** @var Uri */
    protected $linkA;
    /** @var Uri */
    protected $linkB;
    /** @var Uri */
    protected $linkC;
    /** @var Uri */
    protected $linkD;
    /** @var Uri */
    protected $linkE;
    /** @var Uri */
    protected $linkF;
    /** @var Uri */
    protected $linkG;

    /** @var Response */
    protected $responseA;
    /** @var Response */
    protected $responseB;
    /** @var Response */
    protected $responseC;
    /** @var Response */
    protected $responseD;
    /** @var Response */
    protected $responseE;
    /** @var Response */
    protected $responseF;
    /** @var Response */
    protected $responseG;

    /** @var string */
    protected $hrefA;
    protected $hrefB;
    protected $hrefC;
    protected $hrefD;
    protected $hrefE;
    protected $hrefF;
    protected $hrefG;

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

        $this->linkA = new Uri($this->hrefA);
        $this->linkB = new Uri($this->hrefB);
        $this->linkC = new Uri($this->hrefC);
        $this->linkD = new Uri($this->hrefD);
        $this->linkE = new Uri($this->hrefE);
        $this->linkF = new Uri($this->hrefF);
        $this->linkG = new Uri($this->hrefG);

        $htmlA = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceA.html');
        $this->responseA = new Response(200, null, $htmlA);

        $htmlB = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceB.html');
        $this->responseB = new Response(200, null, $htmlB);

        $htmlC = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceC.html');
        $this->responseC = new Response(200, null, $htmlC);

        $htmlD = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceD.html');
        $this->responseD = new Response(200, null, $htmlD);

        $htmlE = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceE.html');
        $this->responseE = new Response(200, null, $htmlE);

        $htmlF = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceF.html');
        $this->responseF = new Response(200, null, $htmlF);

        $htmlG = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceG.html');
        $this->responseG = new Response(200, null, $htmlG);

        $this->requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(array($this, 'doTestRequest')));

        $this->spider->setRequestHandler($this->requestHandler);
        $this->spider->addDiscoverer(new XPathExpressionDiscoverer('//a'));
    }

    /**
     * @return Resource
     * @throws \ErrorException
     */
    public function doTestRequest()
    {
        $link = func_get_arg(0);

        switch ($link->toString()) {
            case $this->linkA->toString():
                return $this->getResource($this->linkA, $this->responseA);
            case $this->linkB->toString():
                return $this->getResource($this->linkB, $this->responseB);
            case $this->linkC->toString():
                return $this->getResource($this->linkC, $this->responseC);
            case $this->linkD->toString():
                return $this->getResource($this->linkD, $this->responseD);
            case $this->linkE->toString():
                return $this->getResource($this->linkE, $this->responseE);
            case $this->linkF->toString():
                return $this->getResource($this->linkF, $this->responseF);
            case $this->linkG->toString():
                return $this->getResource($this->linkG, $this->responseG);
            default:
                throw new \ErrorException('The requested URI was not stubbed: ' . $link->toString());
        }
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     * Behaviour as explained here: https://en.wikipedia.org/wiki/Depth-first_search#Example
     */
    public function testCrawlDFSDefaultBehaviour()
    {
        $this->spider->setMaxDepth(10);
        $this->spider->setMaxQueueSize(50);

        $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefE,
            $this->hrefF,
            $this->hrefC,
            $this->hrefG,
            $this->hrefB,
            $this->hrefD
        );

        $stats = $this->spider->getStatsHandler();

        foreach ($stats->getQueued() as $index => $uri) {
            $this->assertEquals($expected[$index], $uri->toString());
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

        $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefB,
            $this->hrefC,
            $this->hrefE,
            $this->hrefD,
            $this->hrefF,
            $this->hrefG
        );

        $stats = $this->spider->getStatsHandler();

        foreach ($stats->getQueued() as $index => $uri) {
            $this->assertEquals($expected[$index], $uri->toString());
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

        $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefE,
            $this->hrefC,
            $this->hrefB,
        );

        $stats = $this->spider->getStatsHandler();

        foreach ($stats->getQueued() as $index => $uri) {
            $this->assertEquals($expected[$index], $uri->toString());
        }
    }

    public function testCrawlBFSMaxDepthOne()
    {
        $this->spider->setMaxDepth(1);
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);

        $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefB,
            $this->hrefC,
            $this->hrefE,
        );

        $stats = $this->spider->getStatsHandler();

        foreach ($stats->getQueued() as $index => $uri) {
            $this->assertEquals($expected[$index], $uri->toString());
        }
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlDFSMaxQueueSize()
    {
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(3);

        $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefE,
            $this->hrefF,
        );

        $stats = $this->spider->getStatsHandler();

        foreach ($stats->getQueued() as $index => $uri) {
            $this->assertEquals($expected[$index], $uri->toString());
        }
    }

    public function testCrawlBFSMaxQueueSize()
    {
        $this->spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);
        $this->spider->setMaxDepth(1000);
        $this->spider->setMaxQueueSize(3);

        $this->spider->crawl();

        $expected = array(
            $this->hrefA,
            $this->hrefB,
            $this->hrefC,
        );

        $stats = $this->spider->getStatsHandler();

        foreach ($stats->getQueued() as $index => $uri) {
            $this->assertEquals($expected[$index], $uri->toString());
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

        $this->spider->crawl();
        $stats = $this->spider->getStatsHandler();

        $this->assertCount(0, $stats->getFiltered(), 'Filtered count');
        $this->assertCount(0, $stats->getQueued(), 'Queued count');
        $this->assertCount(1, $stats->getFailed(), 'Failed count');
    }
}

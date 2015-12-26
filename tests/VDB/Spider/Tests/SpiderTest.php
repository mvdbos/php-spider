<?php
namespace VDB\Spider;

use Exception;
use Guzzle\Http\Message\Response;
use PHPUnit_Framework_MockObject_MockObject;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\StatsHandler;
use VDB\Spider\Uri\FilterableUri;

/**
 */
class SpiderTest extends TestCase
{
    /**
     * @var Spider
     */
    protected $spider;

    /**
     * @var StatsHandler
     */
    protected $statsHandler;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestHandler;

    /** @var FilterableUri */
    protected $linkA;
    /** @var FilterableUri */
    protected $linkB;
    /** @var FilterableUri */
    protected $linkC;
    /** @var FilterableUri */
    protected $linkD;
    /** @var FilterableUri */
    protected $linkE;
    /** @var FilterableUri */
    protected $linkF;
    /** @var FilterableUri */
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

        $this->linkA = new FilterableUri($this->hrefA);
        $this->linkB = new FilterableUri($this->hrefB);
        $this->linkC = new FilterableUri($this->hrefC);
        $this->linkD = new FilterableUri($this->hrefD);
        $this->linkE = new FilterableUri($this->hrefE);
        $this->linkF = new FilterableUri($this->hrefF);
        $this->linkG = new FilterableUri($this->hrefG);

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

        $this->statsHandler = new StatsHandler();
        $this->spider->getDispatcher()->addSubscriber($this->statsHandler);
        $this->spider->getQueueManager()->getDispatcher()->addSubscriber($this->statsHandler);

        $this->logHandler = new LogHandler();
        $this->spider->getDispatcher()->addSubscriber($this->logHandler);
        $this->spider->getQueueManager()->getDispatcher()->addSubscriber($this->logHandler);
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
        $this->spider->getQueueManager()->maxDepth = 10;

        $this->spider->crawl();

        $expected = array(
            $this->linkA,
            $this->linkE,
            $this->linkF,
            $this->linkC,
            $this->linkG,
            $this->linkB,
            $this->linkD
        );

        $this->assertEquals($expected, $this->statsHandler->getPersisted());
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     */
    public function testCrawlBFSDefaultBehaviour()
    {
        $this->spider->getQueueManager()->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_BREADTH_FIRST);
        $this->spider->getQueueManager()->maxDepth = 1000;

        $this->spider->crawl();

        $expected = array(
            $this->linkA,
            $this->linkB,
            $this->linkC,
            $this->linkE,
            $this->linkD,
            $this->linkF,
            $this->linkG
        );

        $this->assertEquals($expected, $this->statsHandler->getPersisted());
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     *
     * Behaviour as explained here: https://en.wikipedia.org/wiki/Depth-first_search#Example
     */
    public function testCrawlDFSMaxDepthOne()
    {
        $this->spider->getQueueManager()->maxDepth = 1;

        $this->spider->crawl();

        $expected = array(
            $this->linkA,
            $this->linkE,
            $this->linkC,
            $this->linkB,
        );

        $this->assertEquals($expected, $this->statsHandler->getPersisted());
    }

    public function testCrawlBFSMaxDepthOne()
    {
        $this->spider->getQueueManager()->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_BREADTH_FIRST);
        $this->spider->getQueueManager()->maxDepth = 1;

        $this->spider->crawl();

        $expected = array(
            $this->linkA,
            $this->linkB,
            $this->linkC,
            $this->linkE,
        );

        $this->assertEquals($expected, $this->statsHandler->getPersisted());
    }

    /**
     * @covers VDB\Spider\Spider::crawl
     */
    public function testCrawlDFSMaxQueueSize()
    {
        $this->spider->getQueueManager()->maxDepth = 1000;
        $this->spider->downloadLimit = 3;

        $this->spider->crawl();

        $expected = array(
            $this->linkA,
            $this->linkE,
            $this->linkF,
        );

        $this->assertEquals($expected, $this->statsHandler->getPersisted());
    }

    public function testCrawlBFSMaxQueueSize()
    {
        $this->spider->getQueueManager()->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_BREADTH_FIRST);
        $this->spider->getQueueManager()->maxDepth = 1000;
        $this->spider->downloadLimit = 3;

        $this->spider->crawl();

        $expected = array(
            $this->linkA,
            $this->linkB,
            $this->linkC,
        );

        $this->assertEquals($expected, $this->statsHandler->getPersisted());
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
        $stats = $this->statsHandler;

        $this->assertCount(0, $stats->getFiltered(), 'Filtered count');
        $this->assertCount(0, $stats->getPersisted(), 'Persisted count');
        $this->assertCount(1, $stats->getFailed(), 'Failed count');
    }
}

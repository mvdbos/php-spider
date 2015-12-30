<?php
namespace VDB\Spider;

use Exception;
use Guzzle\Http\Message\Response;
use PHPUnit_Framework_MockObject_MockObject;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\StatsHandler;
use VDB\Spider\Uri\DiscoveredUri;
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
     * @var logHandler
     */
    protected $logHandler;

    /**
     * @var StatsHandler
     */
    protected $statsHandler;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestHandler;

    /** @var DiscoveredUri */
    protected $linkA;
    /** @var DiscoveredUri */
    protected $linkB;
    /** @var DiscoveredUri */
    protected $linkC;
    /** @var DiscoveredUri */
    protected $linkD;
    /** @var DiscoveredUri */
    protected $linkE;
    /** @var DiscoveredUri */
    protected $linkF;
    /** @var DiscoveredUri */
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
     *
     * Setting up the following structure:
     *
     * 0:        A
     *          /|\
     * 1:      B C E
     *        /| | |
     * 2:    D F G |
     *         | _ |
     *
     * Note: E links to F.
     */
    protected function setUp()
    {
        $this->spider = new Spider('http://php-spider.org/A');

        $this->requestHandler = $this->getMock('VDB\Spider\RequestHandler\RequestHandlerInterface');

        $this->hrefA = 'http://php-spider.org/A';
        $this->hrefB = 'http://php-spider.org/B';
        $this->hrefC = 'http://php-spider.org/C';
        $this->hrefD = 'http://php-spider.org/D';
        $this->hrefE = 'http://php-spider.org/E';
        $this->hrefF = 'http://php-spider.org/F';
        $this->hrefG = 'http://php-spider.org/G';

        $this->linkA = new DiscoveredUri(new Uri($this->hrefA));
        $this->linkB = new DiscoveredUri(new Uri($this->hrefB));
        $this->linkC = new DiscoveredUri(new Uri($this->hrefC));
        $this->linkD = new DiscoveredUri(new Uri($this->hrefD));
        $this->linkE = new DiscoveredUri(new Uri($this->hrefE));
        $this->linkF = new DiscoveredUri(new Uri($this->hrefF));
        $this->linkG = new DiscoveredUri(new Uri($this->hrefG));

        $this->linkA->setDepthFound(0);
        $this->linkB->setDepthFound(1);
        $this->linkC->setDepthFound(1);
        $this->linkD->setDepthFound(2);
        $this->linkE->setDepthFound(1);
        $this->linkF->setDepthFound(2);
        $this->linkG->setDepthFound(2);

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

        $this->spider->getDownloader()->setRequestHandler($this->requestHandler);

        $this->spider->getDiscovererSet()->set(new XPathExpressionDiscoverer('//a'));

        $this->statsHandler = new StatsHandler();
        $this->spider->getDispatcher()->addSubscriber($this->statsHandler);
        $this->spider->getQueueManager()->getDispatcher()->addSubscriber($this->statsHandler);
        $this->spider->getDownloader()->getDispatcher()->addSubscriber($this->statsHandler);

        $this->logHandler = new LogHandler();
        $this->spider->getDispatcher()->addSubscriber($this->logHandler);
        $this->spider->getQueueManager()->getDispatcher()->addSubscriber($this->logHandler);
        $this->spider->getDownloader()->getDispatcher()->addSubscriber($this->logHandler);
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
        $this->spider->getDiscovererSet()->maxDepth = 10;

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
        $this->spider->getDiscovererSet()->maxDepth = 1000;

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
     *
     * Given the following structure:
     *
     * 0:        A
     *          /|\
     * 1:      B C E
     *        /| | |
     * 2:    D F G |
     *         | _ |
     *
     * We expect the following result: A, E, C, B
     *
     */
    public function testCrawlDFSMaxDepthOne()
    {
        $this->spider->getDiscovererSet()->maxDepth = 1;

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
        $this->spider->getDiscovererSet()->maxDepth = 1;

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
        $this->spider->getDiscovererSet()->maxDepth = 1000;
        $this->spider->getDownloader()->setDownloadLimit(3);

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
        $this->spider->getDiscovererSet()->maxDepth = 1000;
        $this->spider->getDownloader()->setDownloadLimit(3);

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

<?php
namespace VDB\Spider;

use Exception;
use GuzzleHttp\Psr7\Response;
use PHPUnit_Framework_MockObject_MockObject;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\QueueManager\InMemoryQueueManager;
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
     * @var array An associative array, containing a map of $this->linkX to $this->responseX.
     */
    protected $linkToResponseMap = [];

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

        $this->requestHandler = $this->getMockBuilder('VDB\Spider\RequestHandler\RequestHandlerInterface')->getMock();

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
        $this->responseA = new Response(200, [], $htmlA);

        $htmlB = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceB.html');
        $this->responseB = new Response(200, [], $htmlB);

        $htmlC = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceC.html');
        $this->responseC = new Response(200, [], $htmlC);

        $htmlD = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceD.html');
        $this->responseD = new Response(200, [], $htmlD);

        $htmlE = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceE.html');
        $this->responseE = new Response(200, [], $htmlE);

        $htmlF = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceF.html');
        $this->responseF = new Response(200, [], $htmlF);

        $htmlG = file_get_contents(__DIR__ . '/Fixtures/SpiderTestHTMLResourceG.html');
        $this->responseG = new Response(200, [], $htmlG);

        $this->linkToResponseMap[$this->linkA->toString()] = $this->responseA;
        $this->linkToResponseMap[$this->linkB->toString()] = $this->responseB;
        $this->linkToResponseMap[$this->linkC->toString()] = $this->responseC;
        $this->linkToResponseMap[$this->linkD->toString()] = $this->responseD;
        $this->linkToResponseMap[$this->linkE->toString()] = $this->responseE;
        $this->linkToResponseMap[$this->linkF->toString()] = $this->responseF;
        $this->linkToResponseMap[$this->linkG->toString()] = $this->responseG;

        $this->requestHandler
            ->expects($this->any())
            ->method('request')
            ->will($this->returnCallback(array($this, 'doTestRequest')));

        $this->spider->getDownloader()->setRequestHandler($this->requestHandler);

        $this->spider->getDiscovererSet()->set(new XPathExpressionDiscoverer('//a'));
    }

    /**
     * @return Resource
     * @throws \ErrorException
     */
    public function doTestRequest()
    {
        $link = func_get_arg(0);

        if (array_key_exists($link->toString(), $this->linkToResponseMap)) {
            return $this->getResource($link, $this->linkToResponseMap[$link->toString()]);
        }

        throw new \ErrorException('The requested URI was not stubbed: ' . $link->toString());
    }

    /**
     * @covers VDB\Spider\Spider
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

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    /**
     * @covers VDB\Spider\Spider
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

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    private function compareUriArray($expected, $actual)
    {
        foreach ($actual as $index => $resource) {
            $this->assertEquals($resource->getUri(), $expected[$index]);
        }
    }

    /**
     * @covers VDB\Spider\Spider
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

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    /**
     * @covers VDB\Spider\Spider
     */
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

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    /**
     * @covers VDB\Spider\Spider
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

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    /**
     * @covers VDB\Spider\Spider
     */
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

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    /**
     * @covers VDB\Spider\Spider
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

        $this->assertCount(0, $this->spider->getDownloader()->getPersistenceHandler(), 'Persisted count');
    }
}

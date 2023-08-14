<?php

namespace VDB\Spider\Tests;

use ErrorException;
use Exception;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Http;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class SpiderTest extends TestCase
{
    protected Spider $spider;
    protected MockObject $requestHandler;

    protected DiscoveredUri $linkA;
    protected DiscoveredUri $linkB;
    protected DiscoveredUri $linkC;
    protected DiscoveredUri $linkD;
    protected DiscoveredUri $linkE;
    protected DiscoveredUri $linkF;
    protected DiscoveredUri $linkG;

    protected Response $responseA;
    protected Response $responseB;
    protected Response $responseC;
    protected Response $responseD;
    protected Response $responseE;
    protected Response $responseF;
    protected Response $responseG;

    protected string $hrefA;
    protected string $hrefB;
    protected string $hrefC;
    protected string $hrefD;
    protected string $hrefE;
    protected string $hrefF;
    protected string $hrefG;

    /**
     * @var array An associative array, containing a map of $this->linkX to $this->responseX.
     */
    protected $linkToResponseMap = [];

    /**
     * @return Resource
     * @throws ErrorException
     */
    public function doTestRequest(): Resource
    {
        $link = func_get_arg(0);

        if (array_key_exists($link->toString(), $this->linkToResponseMap)) {
            return $this->getResource($link, $this->linkToResponseMap[$link->toString()]);
        }

        throw new ErrorException('The requested URI was not stubbed: ' . $link->toString());
    }

    /**
     * @covers \VDB\Spider\Spider
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

    private function compareUriArray($expected, $actual)
    {
        $this->assertSameSize($expected, $actual);

        foreach ($actual as $index => $resource) {
            $this->assertEquals($resource->getUri(), $expected[$index]);
        }
    }

    /**
     * @covers \VDB\Spider\Spider
     */
    public function testCrawlBFSDefaultBehaviour()
    {
        $this->spider->getQueueManager()->setTraversalAlgorithm(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
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

    /**
     * @covers \VDB\Spider\Spider
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
     * @covers \VDB\Spider\Spider
     */
    public function testCrawlBFSMaxDepthOne()
    {
        $this->spider->getQueueManager()->setTraversalAlgorithm(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
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
     * @covers \VDB\Spider\Spider
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
     * @covers \VDB\Spider\Spider
     */
    public function testCrawlBFSMaxQueueSize()
    {
        $this->spider->getQueueManager()->setTraversalAlgorithm(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);
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
     * @covers \VDB\Spider\Spider
     */
    public function testMaxQueueSizeExceeded()
    {
        $qm = new InMemoryQueueManager();
        $qm->maxQueueSize = 1;
        $this->spider->setQueueManager($qm);

        $this->spider->crawl();

        $expected = array(
            $this->linkA
        );

        $this->compareUriArray($expected, $this->spider->getDownloader()->getPersistenceHandler());
    }

    /**
     * @covers \VDB\Spider\Spider
     */
    public function testInvalidSeedFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid seed');
        new Spider('fdsfnsd:t4rgevjk lffdsn');
    }


    /**
     * @covers \VDB\Spider\Spider
     */
    public function testEmptySeedFails()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty seed');
        new Spider('');
    }

    /**
     * @covers \VDB\Spider\Spider
     */
    public function testSpiderId()
    {
        $spider = new Spider(
            'http://example.com',
            null,
            null,
            null,
            'MyID'
        );
        $this->assertEquals('MyID', $spider->getSpiderId());
    }

    /**
     * @covers \VDB\Spider\Spider
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

    /**
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
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    protected function setUp(): void
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

        $this->linkA = new DiscoveredUri(new Http($this->hrefA), 0);
        $this->linkB = new DiscoveredUri(new Http($this->hrefB), 1);
        $this->linkC = new DiscoveredUri(new Http($this->hrefC), 1);
        $this->linkD = new DiscoveredUri(new Http($this->hrefD), 2);
        $this->linkE = new DiscoveredUri(new Http($this->hrefE), 1);
        $this->linkF = new DiscoveredUri(new Http($this->hrefF), 2);
        $this->linkG = new DiscoveredUri(new Http($this->hrefG), 2);

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
}

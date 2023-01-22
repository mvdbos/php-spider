<?php

use Example\LogHandler;
use Example\StatsHandler;
use GuzzleHttp\Middleware;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Spider;


require_once('example_complex_bootstrap.php');

// The URI we want to start crawling with
$seed = 'https://www.dmoz-odp.org/Computers/Internet/';

// We want to allow all subdomains of dmoz.org
$allowSubDomains = true;

// Create spider
$spider = new Spider($seed);
$spider->getDownloader()->setDownloadLimit(10);

$statsHandler = new StatsHandler();
$LogHandler = new LogHandler();

$queueManager = new InMemoryQueueManager();

$queueManager->getDispatcher()->addSubscriber($statsHandler);
$queueManager->getDispatcher()->addSubscriber($LogHandler);

// Set some sane defaults for this example.
// We only visit the first level of http://dmoztools.net. We stop at 10 queued resources
$spider->getDiscovererSet()->maxDepth = 1;

// This time, we set the traversal algorithm to breadth-first. The default is depth-first
$queueManager->setTraversalAlgorithm(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);

$spider->setQueueManager($queueManager);

// We add an URI discoverer. Without it, the spider wouldn't get past the seed resource.
$spider->getDiscovererSet()->set(new XPathExpressionDiscoverer("//*[@id='cat-list-content-2']/div/a"));

// Let's tell the spider to save all found resources on the filesystem
$spider->getDownloader()->setPersistenceHandler(
    new FileSerializedResourcePersistenceHandler(__DIR__ . '/results')
);

// Add some prefetch filters. These are executed before a resource is requested.
// The more you have of these, the less HTTP requests and work for the processors
$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(array('http', 'https')));
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));
$spider->getDiscovererSet()->addFilter(new UriWithHashFragmentFilter());
$spider->getDiscovererSet()->addFilter(new UriWithQueryStringFilter());

// We add an event listener to the crawler that implements a politeness policy.
// We wait 100ms between every request to the same domain
$politenessPolicyEventListener = new PolitenessPolicyListener(100);
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
    array($politenessPolicyEventListener, 'onCrawlPreRequest')
);

$spider->getDispatcher()->addSubscriber($statsHandler);
$spider->getDispatcher()->addSubscriber($LogHandler);

// Let's add something to enable us to stop the script
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_USER_STOPPED,
    function (GenericEvent $event) {
        echo "\nCrawl aborted by user.\n";
        exit();
    }
);

// Let's add a CLI progress meter for fun
echo "\nCrawling";
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_POST_REQUEST,
    function (GenericEvent $event) {
        echo '.';
    }
);

// Set up some caching, logging and profiling on the HTTP client of the spider
$guzzleClient = $spider->getDownloader()->getRequestHandler()->getClient();
$tapMiddleware = Middleware::tap([$timerMiddleware, 'onRequest'], [$timerMiddleware, 'onResponse']);
$guzzleClient->getConfig('handler')->push($tapMiddleware, 'timer');

// Execute the crawl
$spider->crawl();

// Report
echo "\n  ENQUEUED:  " . count($statsHandler->getQueued());
echo "\n  SKIPPED:   " . count($statsHandler->getFiltered());
echo "\n  FAILED:    " . count($statsHandler->getFailed());
echo "\n  PERSISTED:    " . count($statsHandler->getPersisted());

// With the information from some of plugins and listeners, we can determine some metrics
$peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$totalTime = round(microtime(true) - $start, 2);
$totalDelay = round($politenessPolicyEventListener->totalDelay / 1000 / 1000, 2);
echo "\n\nMETRICS:";
echo "\n  PEAK MEM USAGE:       " . $peakMem . 'MB';
echo "\n  TOTAL TIME:           " . $totalTime . 's';
echo "\n  REQUEST TIME:         " . $timerMiddleware->getTotal() . 's';
echo "\n  POLITENESS WAIT TIME: " . $totalDelay . 's';
echo "\n  PROCESSING TIME:      " . ($totalTime - $timerMiddleware->getTotal() - $totalDelay) . 's';

// Finally we could start some processing on the downloaded resources
echo "\n\nDOWNLOADED RESOURCES: ";
$downloaded = $spider->getDownloader()->getPersistenceHandler();
foreach ($downloaded as $resource) {
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = (int)$resource->getResponse()->getHeaderLine('Content-Length');
    $contentLengthString = '';
    if ($contentLength >= 1024) {
        $contentLengthString = str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB]";
    } else {
        $contentLengthString = str_pad("[" . $contentLength, 5, ' ', STR_PAD_LEFT) . "B]";
    }
    $uri = $resource->getUri()->toString();
    echo "\n - " . $contentLengthString . " $title ($uri)";
}
echo "\n";

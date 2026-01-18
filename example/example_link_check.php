<?php

use Example\LogHandler;
use GuzzleHttp\Middleware;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\Spider;
use Example\StatsHandler;

/*
 * Link Checker Example
 * ====================
 * 
 * This example demonstrates how to use PHP-Spider as a link checker to find broken links.
 * It's almost identical to example_complex, with one critical difference:
 * 
 * KEY DIFFERENCE:
 * - Uses LinkCheckRequestHandler instead of the default GuzzleRequestHandler
 * - LinkCheckRequestHandler sets 'http_errors' => false in Guzzle
 * - This prevents exceptions on 4XX/5XX responses, allowing the spider to continue
 * - Failed requests are still persisted with their status codes
 * 
 * Use Cases:
 * - Finding 404 pages on your website
 * - Validating external links
 * - Checking for broken redirects (3XX issues)
 * - Monitoring website health
 * - Pre-deployment link validation
 * 
 * Default Behavior vs Link Checking:
 * - Default: Spider stops crawling when it encounters 4XX/5XX errors
 * - Link Checker: Spider continues crawling and captures error status codes
 * 
 * For health checking with lightweight JSON output, see example_health_check.php
 */

require_once('example_complex_bootstrap.php');

// The URI we want to start crawling with
$seed = 'https://www.dmoz-odp.org/';

// We want to allow all subdomains of dmoz.org
$allowSubDomains = true;

// Create spider instance
$spider = new Spider($seed);
$spider->getDownloader()->setDownloadLimit(10);

// CRITICAL: Set a custom request handler that tolerates HTTP errors
// LinkCheckRequestHandler extends GuzzleRequestHandler but sets 'http_errors' => false
// This allows the spider to capture 4XX and 5XX responses instead of throwing exceptions
// Without this, the spider would stop on the first error
$spider->getDownloader()->setRequestHandler(new \Example\LinkCheckRequestHandler());

$statsHandler = new StatsHandler();
$LogHandler = new LogHandler();

$queueManager = new InMemoryQueueManager();

$queueManager->getDispatcher()->addSubscriber($statsHandler);
$queueManager->getDispatcher()->addSubscriber($LogHandler);

// Set crawl limits
// - setMaxDepth(1): Only check links on the seed page and one level deep
// - setTraversalAlgorithm(BREADTH_FIRST): Check all links at depth 0 before depth 1
$spider->getDiscovererSet()->setMaxDepth(1);

// This time, we set the traversal algorithm to breadth-first. The default is depth-first
$queueManager->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_BREADTH_FIRST);

$spider->setQueueManager($queueManager);

// Add URI discoverer
// Find all <a> tags on the page (more comprehensive than example_complex)
//$spider->getDiscovererSet()->addDiscoverer(new XPathExpressionDiscoverer("//*[@id='cat-list-content-2']/div/a"));
$spider->getDiscovererSet()->addDiscoverer(new XPathExpressionDiscoverer("//a"));

// Let's tell the spider to save all found resources on the filesystem
$spider->getDownloader()->setPersistenceHandler(
    new \VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler(__DIR__ . '/results')
);

// Add prefetch filters to focus the link check
// These filters run before downloading, saving time and bandwidth
//$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(array('http')));
$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(array('https', 'http')));
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));
$spider->getDiscovererSet()->addFilter(new UriWithHashFragmentFilter());
$spider->getDiscovererSet()->addFilter(new UriWithQueryStringFilter());

// Add politeness policy
// Wait 100ms between requests to avoid overloading the server
// This is especially important for link checking which may make many requests
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

// Execute the link check
// The spider will now check all links and capture their status codes
$result = $spider->crawl();

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

// Display link check results
// Each resource includes its HTTP status code, even for errors
echo "\n\nDOWNLOADED RESOURCES: ";
$downloaded = $spider->getDownloader()->getPersistenceHandler();

/** @var \VDB\Spider\Resource $resource */
foreach ($downloaded as $resource) {
    // Extract status information
    $code = $resource->getResponse()->getStatusCode();
    $reason = $resource->getResponse()->getReasonPhrase();
    
    // Try to get the page title, with fallback for error pages
    $title = $resource->getCrawler()->filterXpath('//title')->text("");
    
    // Format content length
    $contentLength = (int)$resource->getResponse()->getHeaderLine('Content-Length');
    $contentLengthString = '';
    if ($contentLength >= 1024) {
        $contentLengthString = str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB]";
    } else {
        $contentLengthString = str_pad("[" . $contentLength, 5, ' ', STR_PAD_LEFT) . "B]";
    }
    
    $uri = $resource->getUri()->toString();
    
    // Display the result with status code
    // This makes it easy to identify broken links (4XX/5XX codes)
    echo "\n - " . $contentLengthString . " $title ($uri) " .$code ." ". $reason;
}
echo "\n";

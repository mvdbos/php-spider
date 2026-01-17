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
 * Health Check Example
 * ====================
 * 
 * This example is designed for checking if pages on a website are healthy (not returning 404 or errors).
 * It differs from other examples in several key ways:
 * 
 * 1. USES CUSTOM REQUEST HANDLER: Like the link checker example, this uses LinkCheckRequestHandler
 *    that does not throw exceptions on failed requests (4XX/5XX responses). This allows the spider
 *    to continue crawling even when it encounters error pages.
 * 
 * 2. USES JSON PERSISTENCE: Instead of storing full page content in binary files (FileSerializedResourcePersistenceHandler),
 *    this example uses JsonHealthCheckPersistenceHandler which stores only the essential health check data:
 *    - URI (the page URL)
 *    - HTTP status code (200, 404, 500, etc.)
 *    - Reason phrase ("OK", "Not Found", etc.)
 *    - Timestamp (when the check was performed)
 *    - Depth (at which depth this URI was discovered)
 * 
 * 3. LIGHTWEIGHT OUTPUT: The JSON file is small and easy to process programmatically, making it ideal for:
 *    - Automated health checks
 *    - CI/CD pipelines
 *    - Monitoring dashboards
 *    - Quick identification of broken pages
 * 
 * 4. FOCUS ON PAGE HEALTH: This example is specifically for checking if your own pages are healthy,
 *    not for analyzing all links on those pages. It crawls your site and reports the status of each page.
 * 
 * Use Case: You want to crawl your website to check if there are any 404 pages or error pages,
 *           but you're not interested in storing the full page content. You just want a simple
 *           report of which pages are working and which are not.
 */

require_once('example_complex_bootstrap.php');

// The URI we want to start crawling with
// Replace this with your own website URL
$seed = 'https://www.dmoz-odp.org/';

// We want to allow all subdomains of dmoz.org
$allowSubDomains = true;

// Create spider
$spider = new Spider($seed);
$spider->getDownloader()->setDownloadLimit(10);

// Set a custom request handler that does not throw exceptions on failed requests
// This is CRITICAL for health checking - it allows us to capture 404s and other errors
$spider->getDownloader()->setRequestHandler(new \Example\LinkCheckRequestHandler());

$statsHandler = new StatsHandler();
$LogHandler = new LogHandler();

$queueManager = new InMemoryQueueManager();

$queueManager->getDispatcher()->addSubscriber($statsHandler);
$queueManager->getDispatcher()->addSubscriber($LogHandler);

// Set some sane defaults for this example. We only visit the first level of www.dmoz.org. We stop at 10 queued resources
$spider->getDiscovererSet()->maxDepth = 1;

// This time, we set the traversal algorithm to breadth-first. The default is depth-first
$queueManager->setTraversalAlgorithm(InMemoryQueueManager::ALGORITHM_BREADTH_FIRST);

$spider->setQueueManager($queueManager);

// We add an URI discoverer. Without it, the spider wouldn't get past the seed resource.
$spider->getDiscovererSet()->set(new XPathExpressionDiscoverer("//a"));

// IMPORTANT: Instead of storing full resources as binary files, we use JsonHealthCheckPersistenceHandler
// This creates a single JSON file with health check data for all crawled pages
$spider->getDownloader()->setPersistenceHandler(
    new \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler(__DIR__ . '/results')
);

// Add some prefetch filters. These are executed before a resource is requested.
// The more you have of these, the less HTTP requests and work for the processors
$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(array('https', 'http')));
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));
$spider->getDiscovererSet()->addFilter(new UriWithHashFragmentFilter());
$spider->getDiscovererSet()->addFilter(new UriWithQueryStringFilter());

// We add an eventlistener to the crawler that implements a politeness policy. We wait 100ms between every request to the same domain
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

// Display health check results
echo "\n\nHEALTH CHECK RESULTS: ";
$healthHandler = $spider->getDownloader()->getPersistenceHandler();

// Count status codes
$statusCounts = [];
$healthyCount = 0;
$errorCount = 0;

foreach ($healthHandler as $result) {
    $statusCode = $result['status_code'];
    
    if (!isset($statusCounts[$statusCode])) {
        $statusCounts[$statusCode] = 0;
    }
    $statusCounts[$statusCode]++;
    
    if ($statusCode >= 200 && $statusCode < 400) {
        $healthyCount++;
    } else {
        $errorCount++;
    }
}

echo "\n\nSTATUS CODE SUMMARY:";
ksort($statusCounts);
foreach ($statusCounts as $code => $count) {
    $indicator = ($code >= 200 && $code < 400) ? '✓' : '✗';
    echo "\n  $indicator $code: $count pages";
}

echo "\n\nOVERALL HEALTH:";
echo "\n  ✓ Healthy (2xx-3xx): $healthyCount pages";
echo "\n  ✗ Errors (4xx-5xx):  $errorCount pages";

// Show detailed list of error pages
if ($errorCount > 0) {
    echo "\n\nERROR PAGES:";
    foreach ($healthHandler as $result) {
        if ($result['status_code'] >= 400) {
            echo "\n  ✗ " . $result['status_code'] . " " . $result['reason_phrase'] . " - " . $result['uri'];
        }
    }
}

// Show where the JSON file was saved
echo "\n\nJSON results saved to:\n";
echo "  " . __DIR__ . "/results/*_health_check.json\n";
echo "\nYou can process this JSON file with other tools or import it into a monitoring dashboard.\n";

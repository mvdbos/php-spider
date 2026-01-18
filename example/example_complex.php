<?php
/**
 * Complex Crawling Example
 * =========================
 * 
 * This is a comprehensive example showing most features of PHP-Spider working together.
 * It demonstrates production-ready crawling with proper etiquette, filtering, and monitoring.
 * 
 * Key Features Demonstrated:
 * - Custom queue manager with breadth-first traversal
 * - Multiple prefetch filters (scheme, hosts, hash fragments, query strings, robots.txt)
 * - File-based persistence (saves resources to disk)
 * - Politeness policy (delays between requests to the same domain)
 * - Event listeners and subscribers for stats and logging
 * - Guzzle middleware for timing and profiling
 * - Performance metrics collection
 * - Advanced XPath expressions for URI discovery
 * - Graceful shutdown handling
 * 
 * This example is designed to be a template for real-world crawling projects.
 */

use Example\LogHandler;
use Example\StatsHandler;
use GuzzleHttp\Middleware;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Spider;


require_once('example_complex_bootstrap.php');

// The URI we want to start crawling with
$seed = 'https://www.dmoz-odp.org/Computers/Internet/';

// Allow all subdomains of the seed domain
// When true, the AllowedHostsFilter will accept dmoz-odp.org, www.dmoz-odp.org, etc.
$allowSubDomains = true;

// Create the spider instance
$spider = new Spider($seed);

// Set download limit to prevent excessive crawling
// The spider will stop after downloading 10 resources
$spider->getDownloader()->setDownloadLimit(10);

// Create event handlers for statistics and logging
// These will track the crawl progress and provide insights
$statsHandler = new StatsHandler();   // Collects statistics about the crawl
$LogHandler = new LogHandler();       // Logs events to console (debug mode off by default)

// Create a custom queue manager
// This allows us to control how URIs are queued and processed
$queueManager = new InMemoryQueueManager();

// Subscribe event handlers to the queue manager
// This allows them to receive events when URIs are enqueued, filtered, etc.
$queueManager->getDispatcher()->addSubscriber($statsHandler);
$queueManager->getDispatcher()->addSubscriber($LogHandler);

// Subscribe the stats handler to the downloader to track download events
$spider->getDownloader()->getDispatcher()->addSubscriber($statsHandler);

// Configure crawl limits
// - setMaxDepth(1): Only crawl the seed page and pages directly linked from it
// - setTraversalAlgorithm: Use breadth-first instead of depth-first (the default)
//   Breadth-first processes all links at depth N before moving to depth N+1
//   Depth-first follows each link chain to its maximum depth before backtracking
$spider->getDiscovererSet()->setMaxDepth(1);

// Set traversal algorithm to breadth-first
// Default is depth-first (QueueManagerInterface::ALGORITHM_DEPTH_FIRST)
$queueManager->setTraversalAlgorithm(QueueManagerInterface::ALGORITHM_BREADTH_FIRST);

// Apply the custom queue manager to the spider
$spider->setQueueManager($queueManager);

// Add a URI discoverer
// XPathExpressionDiscoverer finds links using XPath
// This expression matches:
// - Links starting with '/' (relative URLs like /page)
// - Links starting with 'http' (absolute URLs like https://example.com/page)
// The bracket notation allows for more complex XPath expressions
$spider->getDiscovererSet()->addDiscoverer(new XPathExpressionDiscoverer("//a[starts-with(@href, '/') or starts-with(@href, 'http')]"));

// Set up file-based persistence
// FileSerializedResourcePersistenceHandler saves each resource to a file
// Resources are serialized and can be loaded later for processing
// Files are organized by spider ID in subdirectories
$spider->getDownloader()->setPersistenceHandler(
    new FileSerializedResourcePersistenceHandler(__DIR__ . '/results')
);

// Add prefetch filters
// These filters run BEFORE a resource is downloaded, reducing unnecessary HTTP requests
// The more filters you have, the fewer requests are made and the faster the crawl

// 1. AllowedSchemeFilter: Only crawl http and https URLs (skip ftp, mailto, etc.)
$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(array('http', 'https')));

// 2. AllowedHostsFilter: Only crawl the seed domain (and optionally subdomains)
//    This keeps the spider focused on the target website
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));

// 3. UriWithHashFragmentFilter: Skip URLs with hash fragments (#section)
//    Hash fragments often point to the same page, just different sections
$spider->getDiscovererSet()->addFilter(new UriWithHashFragmentFilter());

// 4. UriWithQueryStringFilter: Skip URLs with query strings (?page=1)
//    Query strings can create infinite crawl loops or duplicate content
$spider->getDiscovererSet()->addFilter(new UriWithQueryStringFilter());

// 5. RobotsTxtDisallowFilter: Respect robots.txt rules
//    This is essential for ethical crawling and following webmaster guidelines
//    Parameters: seed URL, user agent string
$spider->getDiscovererSet()->addFilter(new RobotsTxtDisallowFilter($seed, 'PHP-Spider'));

// Add a politeness policy
// PolitenessPolicyListener enforces a delay between requests to the same domain
// This is essential for ethical crawling and prevents overloading target servers
// Delay is in milliseconds (100ms = 0.1 seconds)
$politenessPolicyEventListener = new PolitenessPolicyListener(100);
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,  // Event fired before each HTTP request
    array($politenessPolicyEventListener, 'onCrawlPreRequest')
);

// Subscribe event handlers to the main spider dispatcher
$spider->getDispatcher()->addSubscriber($statsHandler);
$spider->getDispatcher()->addSubscriber($LogHandler);

// Handle user interrupts gracefully (Ctrl+C, SIGTERM, SIGINT)
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_USER_STOPPED,
    function (GenericEvent $event) {
        echo "\nCrawl aborted by user.\n";
        exit();
    }
);

// Add a simple progress indicator
// This prints a dot after each successful request
echo "\nCrawling";
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_POST_REQUEST,
    function (GenericEvent $event) {
        echo '.';
    }
);

// Add Guzzle middleware for timing and profiling
// The timer middleware tracks how long HTTP requests take
// This is useful for performance analysis and optimization
$guzzleClient = $spider->getDownloader()->getRequestHandler()->getClient();
$tapMiddleware = Middleware::tap([$timerMiddleware, 'onRequest'], [$timerMiddleware, 'onResponse']);
$guzzleClient->getConfig('handler')->push($tapMiddleware, 'timer');

// Execute the crawl
// This starts the main crawl loop and processes URIs until limits are reached
$spider->crawl();

// Display statistics report
// These numbers help understand what happened during the crawl
echo "\n  ENQUEUED:  " . count($statsHandler->getQueued());    // Total URIs discovered and queued
echo "\n  SKIPPED:   " . count($statsHandler->getFiltered());  // URIs filtered out by prefetch filters
echo "\n  FAILED:    " . count($statsHandler->getFailed());    // URIs that failed to download
echo "\n  PERSISTED:    " . count($statsHandler->getPersisted()); // URIs successfully downloaded and saved

// Calculate and display performance metrics
// These metrics help identify bottlenecks and optimize crawl performance
$peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$totalTime = round(microtime(true) - $start, 2);
$totalDelay = round($politenessPolicyEventListener->totalDelay / 1000 / 1000, 2);
echo "\n\nMETRICS:";
echo "\n  PEAK MEM USAGE:       " . $peakMem . 'MB';          // Maximum memory used
echo "\n  TOTAL TIME:           " . $totalTime . 's';          // Total execution time
echo "\n  REQUEST TIME:         " . $timerMiddleware->getTotal() . 's';  // Time spent on HTTP requests
echo "\n  POLITENESS WAIT TIME: " . $totalDelay . 's';         // Time spent waiting (politeness delays)
echo "\n  PROCESSING TIME:      " . ($totalTime - $timerMiddleware->getTotal() - $totalDelay) . 's';  // Time spent processing

// Process downloaded resources
// Each resource contains the URI, HTTP response, and a Symfony Crawler for parsing
echo "\n\nDOWNLOADED RESOURCES: ";
$downloaded = $spider->getDownloader()->getPersistenceHandler();
foreach ($downloaded as $resource) {
    // Extract page information
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = (int)$resource->getResponse()->getHeaderLine('Content-Length');
    
    // Format content length for display
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

// Display failed resources
// This helps identify broken links or server issues
echo "\nFAILED RESOURCES: ";
foreach ($statsHandler->getFailed() as $uri => $message) {
    echo "\n - " . $uri . " failed because: " . $message;
}
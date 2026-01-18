<?php
/**
 * Simple Crawling Example
 * =======================
 * 
 * This example demonstrates the basic usage of PHP-Spider.
 * It shows the minimum setup required to crawl a website and process results.
 * 
 * Key Concepts:
 * - Creating a Spider instance with a seed URL
 * - Adding URI discoverers to find links
 * - Setting crawl limits (depth, queue size)
 * - Using event listeners for statistics
 * - Processing downloaded resources
 * - Gracefully handling user interrupts
 */

use Example\StatsHandler;
use VDB\Spider\Discoverer\SimpleXPathExpressionDiscoverer;
use Symfony\Contracts\EventDispatcher\Event;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Spider;

require_once('example_complex_bootstrap.php');

// Create Spider instance
// The seed URL is where the crawl starts
$seed = 'https://www.dmoz-odp.org/';
$spider = new Spider($seed);

// Add a URI discoverer to find links on pages
// Without a discoverer, the spider would only download the seed URL and stop.
// SimpleXPathExpressionDiscoverer uses XPath to find <a> tags.
// In this case, we only want <a> tags from a specific <div> with id='catalogs'
$spider->getDiscovererSet()->addDiscoverer(new SimpleXPathExpressionDiscoverer("//div[@id='catalogs']//a"));

// Set crawl limits to keep this example manageable
// - setMaxDepth(1): Only crawl the seed page (depth 0) and pages linked from it (depth 1)
//                   Prevents crawling the entire website
// - setMaxQueueSize(10): Stop discovering new URIs after 10 are queued
//                        Keeps this example fast
$spider->getDiscovererSet()->setMaxDepth(1);
$spider->getQueueManager()->setMaxQueueSize(10);

// Handle user interrupts (Ctrl+C, SIGTERM, SIGINT)
// This allows the spider to gracefully stop when the user interrupts the script
// The SPIDER_CRAWL_USER_STOPPED event is fired when a signal is received
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_USER_STOPPED,
    function (Event $event) {
        echo "\nCrawl aborted by user.\n";
        exit();
    }
);

// Add a statistics collector using the event system
// StatsHandler is an event subscriber that tracks:
// - Enqueued URIs (discovered and added to queue)
// - Filtered URIs (skipped by filters)
// - Failed requests (errors during download)
// - Persisted resources (successfully downloaded)
// We subscribe it to both the Spider and QueueManager to capture all events
$statsHandler = new StatsHandler();
$spider->getQueueManager()->getDispatcher()->addSubscriber($statsHandler);
$spider->getDispatcher()->addSubscriber($statsHandler);

// Execute the crawl
// This is where the spider actually starts working:
// 1. Downloads the seed URL
// 2. Discovers links using the discoverers
// 3. Adds discovered URIs to the queue
// 4. Repeats for each queued URI until limits are reached
$spider->crawl();

// Build a statistics report
// Show how many URIs were processed in different ways
echo "\n  ENQUEUED:  " . count($statsHandler->getQueued());    // URIs added to the queue
echo "\n  SKIPPED:   " . count($statsHandler->getFiltered());  // URIs filtered out (not downloaded)
echo "\n  FAILED:    " . count($statsHandler->getFailed());    // URIs that failed to download
echo "\n  PERSISTED:    " . count($statsHandler->getPersisted()); // Successfully downloaded URIs

// Process the downloaded resources
// Each resource contains:
// - The original URI
// - The HTTP response (status, headers, body)
// - A Symfony Crawler for parsing HTML/XML (automatically created from response)
echo "\n\nDOWNLOADED RESOURCES: ";
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    // Use Symfony's Crawler to extract the page title using XPath
    echo "\n - " . $resource->getCrawler()->filterXpath('//title')->text();
}

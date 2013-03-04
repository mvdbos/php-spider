<?php
use VDB\Spider\Spider;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;

use Symfony\Component\EventDispatcher\Event;

require_once('example_complex_bootstrap.php');

// The URI we want to start crawling with
$seed = 'http://www.dmoz.org/Computers/Internet/';

// We want to allow all subdomains of dmoz.org
$allowSubDomains = true;

// Create spider
$spider = new Spider($seed);

// Set some sane defaults for this example. We only visit the first level of www.dmoz.org. We stop at 10 queued resources
$spider->setMaxDepth(1);
$spider->setMaxQueueSize(10);

// We add an URI discoverer. Without it, the spider wouldn't get past the seed resource.
$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@class='dir-1 borN'][2]//a"));

// This time, we set the traversal algorithm to breadth-first. The default is depth-first
$spider->setTraversalAlgorithm(Spider::ALGORITHM_BREADTH_FIRST);

// Add some prefetch filters. These are executed before a resource is requested.
// The more you have of these, the less HTTP requests and work for the processors
$spider->addPreFetchFilter(new AllowedSchemeFilter(array('http')));
$spider->addPreFetchFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));
$spider->addPreFetchFilter(new UriWithHashFragmentFilter());
$spider->addPreFetchFilter(new UriWithQueryStringFilter());

// We add an eventlistener to the crawler that implements a politeness policy. We wait 450ms between every request to the same domain
$politenessPolicyEventListener = new PolitenessPolicyListener(450);
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
    array($politenessPolicyEventListener, 'onCrawlPreRequest')
);

// Let's add a CLI progress meter for fun
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_ENQUEUE,
    function (Event $event) {
        echo '.';
    }
);

// Set up some caching, logging and profiling on the HTTP client of the spider
$guzzleClient = $spider->getRequestHandler()->getClient()->getClient();
$guzzleClient->addSubscriber($logPlugin);
$guzzleClient->addSubscriber($timerPlugin);
$guzzleClient->addSubscriber($cachePlugin);

// Set the user agent
$guzzleClient->setUserAgent('Googlebot');

// Execute the crawl
$result = $spider->crawl();

// Let't echo a report
echo "\n\nENQUEUED: " . count($result['queued']);
echo "\n - " . implode("\n - ", $result['queued']);
echo "\n\nSKIPPED:   " . count($result['filtered']);
echo "\n" . var_export($result['filtered'], true);
echo "\n\nFAILED:    " . count($result['failed']);
echo "\n" . var_export($result['failed'], true);

// With the information from some of plugins and listeners, we can determine some metrics
$peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$totalTime = round(microtime(true) - $start, 2);
$totalDelay = round($politenessPolicyEventListener->totalDelay / 1000 / 1000, 2);
echo "\n\nMETRICS:";
echo "\n  PEAK MEM USAGE:       " . $peakMem . 'MB';
echo "\n  TOTAL TIME:           " . $totalTime . 's';
echo "\n  REQUEST TIME:         " . $timerPlugin->getTotal() . 's';
echo "\n  POLITENESS WAIT TIME: " . $totalDelay . 's';
echo "\n  PROCESSING TIME:      " . ($totalTime - $timerPlugin->getTotal() - $totalDelay) . 's' . "\n\n";

// Finally we could start some processing on the downloaded resources
foreach ($result['queued'] as $resource) {
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = $resource->getResponse()->getHeader('Content-Length');
    // do something with the data
    echo "\n - ".  str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB] $title";
}

<?php

use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Spider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Spider
$spider = new Spider('http://www.dmoz.org');

// Add a URI discoverer. Without it, the spider does nothing. In this case, we want <a> tags from a certain <div>
$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@id='catalogs']//a"));

// Set some sane options for this example. In this case, we only get the first 10 items from the start page.
$spider->setMaxDepth(1);
$spider->setMaxQueueSize(10);

// Execute crawl
$spider->crawl();

// Report
$stats = $spider->getStatsHandler();
$spiderId = $stats->getSpiderId();
$queued = $stats->getQueued();
$filtered = $stats->getFiltered();
$failed = $stats->getFailed();

echo "\nSPIDER ID: " . $spiderId;
echo "\n  ENQUEUED:  " . count($queued);
echo "\n  SKIPPED:   " . count($filtered);
echo "\n  FAILED:    " . count($failed);

// Finally we could start some processing on the downloaded resources
echo "\n\nDOWNLOADED RESOURCES: ";

$downloaded = $spider->getPersistenceHandler();
foreach ($downloaded as $resource) {
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = $resource->getResponse()->getHeader('Content-Length');
    // do something with the data
    echo "\n - " . str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB] $title";
}

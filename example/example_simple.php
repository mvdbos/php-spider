<?php
use VDB\Spider\Spider;
use VDB\Spider\Resource;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;

require_once __DIR__  . '/../vendor/autoload.php';

// Create Spider
$spider = new Spider('http://www.dmoz.org');

// Add a URI discoverer. Without it, the spider does nothing. In this case, we want <a> tags from a certain <div>
$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@id='catalogs']//a"));

// Set some sane options for this example. In this case, we only get the first 10 items from the start page.
$spider->setMaxDepth(1);
$spider->setMaxQueueSize(10);

// Execute crawl
$report = $spider->crawl();

// Report
echo "\n\nENQUEUED for processing: " . count($report['queued']);
echo "\n - ".implode("\n - ", $report['queued']);
echo "\n\nSKIPPED:   " . count($report['filtered']);
echo "\n".var_export($report['filtered'], true);
echo "\n\nFAILED:    " . count($report['failed']) ;
echo "\n".var_export($report['failed'], true) . "\n\n";

// Finally we could start some processing on the downloaded resources
foreach ($report['queued'] as $resource) {
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = $resource->getResponse()->getHeader('Content-Length');
    // do something with the data
    echo "\n - ".  str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB] $title";
}

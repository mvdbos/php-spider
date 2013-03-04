<?php
use VDB\Spider\Spider;
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
echo "\n\nENQUEUED: " . count($report['queued']);
echo "\n - ".implode("\n - ", $report['queued']);
echo "\n\nSKIPPED:   " . count($report['filtered']);
echo "\n".var_export($report['filtered'], true);
echo "\n\nFAILED:    " . count($report['failed']) ;
echo "\n".var_export($report['failed'], true);

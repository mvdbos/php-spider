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
$result = $spider->crawl();

// Report
echo "\n\nENQUEUED for processing: " . count($result['queued']);
echo "\n - ".implode("\n - ", $result['queued']);
echo "\n\nSKIPPED:   " . count($result['filtered']);
echo "\n".var_export($result['filtered'], true);
echo "\n\nFAILED:    " . count($result['failed']) ;
echo "\n".var_export($result['failed'], true) . "\n\n";

// Finally we could start some processing on the downloaded resources
foreach ($result['queued'] as $resource) {
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = $resource->getResponse()->getHeader('Content-Length');
    // do something with the data
    echo "\n - ".  str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB] $title";
}

<?php
/**
 * Example demonstrating the CachedResourceFilter
 * 
 * This shows how to use the cache filter to avoid re-downloading
 * resources that are already cached and fresh.
 */

use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\CachedResourceFilter;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\Spider;

require_once(__DIR__ . '/example_complex_bootstrap.php');

// The URI we want to start crawling with
$seed = 'https://www.dmoz-odp.org/Computers/Internet/';

// Use a fixed spider ID so we can share cache across runs
$spiderId = 'example-cached-spider';

// Create spider with fixed ID
$spider = new Spider($seed, null, null, null, $spiderId);
$spider->getDownloader()->setDownloadLimit(5);

// Set some sane defaults for this example
$spider->getDiscovererSet()->maxDepth = 1;

// Add URI discoverer
$spider->getDiscovererSet()->set(new XPathExpressionDiscoverer("//a[starts-with(@href, '/') or starts-with(@href, 'http')]"));

// Set up file persistence
$resultsPath = __DIR__ . '/cache';
$spider->getDownloader()->setPersistenceHandler(
    new FileSerializedResourcePersistenceHandler($resultsPath)
);

// Add standard prefetch filters
$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(array('http', 'https')));
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(array($seed), true));

// Add the cache filter - resources cached within the last hour will be skipped
// Set maxAge to 0 to always use cache regardless of age
$maxAgeSeconds = 3600; // 1 hour
$cacheFilter = new CachedResourceFilter($resultsPath, $spiderId, $maxAgeSeconds);
$spider->getDiscovererSet()->addFilter($cacheFilter);

echo "\nStarting crawl with cache enabled (maxAge: {$maxAgeSeconds}s)...\n";
echo "Cache directory: {$resultsPath}/{$spiderId}\n";
echo "On first run, all resources will be downloaded.\n";
echo "On subsequent runs within {$maxAgeSeconds}s, cached resources will be skipped.\n\n";

// Execute the crawl
$spider->crawl();

echo "\nCrawl complete!\n";
echo "Persisted resources: " . $spider->getDownloader()->getPersistenceHandler()->count() . "\n";

// Show cache statistics
$cacheDir = $resultsPath . DIRECTORY_SEPARATOR . $spiderId;
if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    $fileCount = iterator_count($files);
    echo "Total files in cache: {$fileCount}\n";
    echo "\nRun this example again to see the cache filter in action!\n";
}

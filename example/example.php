<?php
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\URI\HttpURI;
use VDB\Spider\Discoverer\RssXPathExpressionDiscoverer;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\Discoverer\CssSelectorDiscoverer;
use VDB\Spider\Filter\Prefetch\AlreadyVisitedFilter;
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\UriWithQueryStringFilter;
use VDB\Spider\Plugin\DebugLogPlugin;

use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;

use Doctrine\Common\Cache\PhpFileCache;

use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MonologLogAdapter;

use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

use Example\Processors\ImageCounterProcessor;
use Example\TimerPlugin;
use Example\Processors\LogProcessor;

$start = microtime(true);

require __DIR__ . '/../vendor/autoload.php';

/**
 * Set up the DI container
 */
$container = new Pimple();

$container['guzzle.plugin.log.request'] = $container->share(
    function ($container) {
        $adapter = new MonologLogAdapter(
            new Logger('spider', array(
                new RotatingFileHandler(__DIR__ . '/logs/guzzle-request.log')
            ))
        );
        return new LogPlugin($adapter, "{url}\t{code}\t{total_time}");
    }
);

$container['spider.plugin.log'] = $container->share(
    function ($container) {
        $logger = new Logger(
            'spider',
            array(
                new RotatingFileHandler(__DIR__ . '/logs/spider-debug.log'),
            ),
            array(
                new MemoryUsageProcessor(),
                new MemoryPeakUsageProcessor()

            )
        );
        return new DebugLogPlugin($logger);
    }
);


$container['guzzle.plugin.timer.request'] = $container->share(
    function ($container) {
        return new TimerPlugin();
    }
);

$container['guzzle.plugin.cache.request'] = $container->share(
    function ($container) {
        return new CachePlugin(array(
            'adapter' => new DoctrineCacheAdapter(new PhpFileCache(__DIR__ . '/cache')),
            'default_ttl' => 0
        ));
    }
);


//HttpURI::$allowedSchemes[] = 'https';
//HttpURI::$allowedSchemes[] = 'mailto';

$spider = new VDB\Spider\Spider($container);

$politenessPolicyEventListener = new PolitenessPolicyListener(500);
//$spider->getDispatcher()->addListener(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, array($politenessPolicyEventListener, 'onCrawlPreRequest'));

// Voorbeeld 0: Hele blog.vandenbos.org site
//HttpURI::$allowedSchemes[] = 'mailto';

$seed = 'http://blog.vandenbos.org/'; $allowSubDomains = false; $maxDepth = 2;
$spider->addDiscoverer(new XPathExpressionDiscoverer("//a"));
//$spider->addDiscoverer(new CssSelectorDiscoverer('a'));

//$seed = 'http://example.com'; $allowSubDomains = true; $maxDepth = 10;
//$spider->addDiscoverer(new XPathExpressionDiscoverer("//a"));

// ### Voorbeeld 1:  Alleen alle links naar detailpagina's van Nu.nl/sport, niet dieper dan eerste detailpagina.
//$seed = 'http://www.nu.nl/sport'; $allowSubDomains = false; $maxDepth = 1;
//$restrictToBaseUriFilter = new CrawlFilterPrefetchSubscriber(
//    new RestrictToBaseUriFilter('http://www.nu.nl/sport')
//);
//$spider = new VDB\Spider\Spider($container, $seed);
//$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@id='middlecolumn']//a[@class='trackevent']"));
//$spider->getDispatcher()->addSubscriber($restrictToBaseUriFilter);



// ### Voorbeeld 2:  Idem, maar dan 2 niveau's diep. Dat levert dezelfde resultaten op, TENZIJ het domein nusport ook toegestaan wordt.
//// TODO: mogelijk maken om meerdere allowed domains te hebben en meerdere allowed baseUris, tot die tijd is dit zinloos
//$seed = 'http://www.nu.nl/sport'; $allowSubDomains = false; $maxDepth = 2;
//$restrictToBaseUriFilter = new CrawlFilterPrefetchSubscriber(
//    new RestrictToBaseUriFilter('http://www.nu.nl/sport')
//);
//$spider = new VDB\Spider\Spider($container, $seed);
//$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@id='middlecolumn']//a[@class='trackevent']"));
//$spider->getDispatcher()->addSubscriber($restrictToBaseUriFilter);



//// ### Voorbeeld 3:  Alleen alle links naar detailpagina's van pagina 1 Tour Nieuws van Golf.nl en geen pagineerlinks, niet dieper dan eerste detailpagina.
//$seed = 'http://www.golf.nl/nieuws/tour'; $allowSubDomains = false; $maxDepth = 1;
//$spider = new VDB\Spider\Spider($container, $seed);
//$spider->addPreFetchFilter(new RestrictToBaseUriFilter('http://www.golf.nl/nieuws/detail'));
//$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@id='page']/div[@id='content']/div[@class='column column-4']//a"));

// ### Voorbeeld 3:  Alleen alle links naar detailpagina's van pagina 1 Tour Nieuws van Golf.nl en geen pagineerlinks, niet dieper dan eerste detailpagina.
//$seed = 'http://www.dmoz.org/'; $allowSubDomains = true; $maxDepth = 3;
//$spider->addDiscoverer(new XPathExpressionDiscoverer("//a"));

//$seed = 'http://www.nu.nl/feeds/rss/algemeen.rss'; $allowSubDomains = false; $maxDepth = 1;
//$spider->addDiscoverer(new XPathExpressionDiscoverer("//item/link"));


/**
 * Set up the Spider itself
 */
$spider->setMaxDepth($maxDepth);

$spider->getDispatcher()->addSubscriber($container['spider.plugin.log']);

// Set up some logging and profiling on the Spiders HTTP client (Guzzle)
$guzzleClient = $spider->getRequestHandler()->getClient()->getClient();
$guzzleClient->addSubscriber($container['guzzle.plugin.log.request']);
$guzzleClient->addSubscriber($container['guzzle.plugin.timer.request']);
$guzzleClient->addSubscriber($container['guzzle.plugin.cache.request']);
$guzzleClient->setUserAgent('Googlebot');


// Add some default PreFetchFilter. The more you have of these, the less HTTP requests and work for the processors
$spider->addPreFetchFilter(new AlreadyVisitedFilter($seed)); // ALWAYS use this, you know why...
$spider->addPreFetchFilter(new AllowedSchemeFilter(array('http')));
$spider->addPreFetchFilter(new AllowedHostsFilter(array($seed), $allowSubDomains));
$spider->addPreFetchFilter(new UriWithHashFragmentFilter());
//$spider->addPreFetchFilter(new UriWithQueryStringFilter());

// Add some processors
//$logProcessor = new LogProcessor();
//$spider->addProcessor($logProcessor);
//$imageProcessor = new ImageCounterProcessor();
//$spider->addProcessor(new ImageCounterProcessor($imageProcessor));

// Execute
$report = $spider->crawl($seed);
$processed = $spider->process();

//echo "\n\nPROCESSED\n";
//asort($processed);
//echo "\n - ".implode("\n - ", $processed);

/**
 * Reporting
 */
echo "\n\nCRAWL REPORT for " . $report['spiderId'] . ':';

$enqueued = $report['queued'];
$filtered = $report['filtered'];
$failed   = $report['failed'];

/** @var $timer TimerPlugin */
$timer = $container['guzzle.plugin.timer.request'];

echo "\n\nENQUEUED\n";
print_r($enqueued);
echo "\n\nFILTERED\n";
print_r($filtered);
echo "\n\nFAILED\n";
print_r($failed);

echo "\n  COUNT ENQUEUED:  " . count($enqueued);
echo "\n  COUNT SKIPPED:   " . count($filtered);
echo "\n  COUNT FAILED:    " . count($failed);

echo "\n\nPROCESS REPORT for " . $report['spiderId'] . ':';
echo "\n  COUNT PROCESSED: " . count($processed);


$peakMem = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
$totalTime = round(microtime(true) - $start, 2);

echo "\n\nMETRICS:";
echo "\n  PEAK MEM USAGE:    " . $peakMem . 'MB';
echo "\n  TOTAL TIME:     " . $totalTime . 's';
echo "\n  REQ TIME:       " . $timer->getTotal() . 's';
echo "\n  PROC TIME:      " . ($totalTime - $timer->getTotal()) . 's';
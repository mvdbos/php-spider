<?php
use Example\TimerPlugin;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Guzzle\Plugin\Cache\CachePlugin;
use Guzzle\Cache\DoctrineCacheAdapter;
use Guzzle\Plugin\Log\LogPlugin;
use Guzzle\Log\MonologLogAdapter;
use Doctrine\Common\Cache\PhpFileCache;

$start = microtime(true);

require __DIR__ . '/../vendor/autoload.php';

$loader = new \Composer\Autoload\ClassLoader();

// register classes with namespaces
$loader->add('Example', __DIR__ . '/lib');

// activate the autoloader
$loader->register();

$adapter = new MonologLogAdapter(
    new Logger('spider', array(
        new RotatingFileHandler(__DIR__ . '/logs/guzzle-request.log')
    ))
);
$logPlugin = new LogPlugin($adapter, "{url}\t{code}\t{total_time}");

$timerPlugin = new TimerPlugin();

$cachePlugin = new CachePlugin(
    array(
        'adapter' => new DoctrineCacheAdapter(new PhpFileCache(__DIR__ . '/cache')),
        'default_ttl' => 0
    )
);

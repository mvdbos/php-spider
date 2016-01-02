<?php
use Example\GuzzleTimerMiddleware;

$start = microtime(true);

require __DIR__ . '/../vendor/autoload.php';

$loader = new \Composer\Autoload\ClassLoader();

// register classes with namespaces
$loader->add('Example', __DIR__ . '/lib');

// activate the autoloader
$loader->register();

$timerMiddleware = new GuzzleTimerMiddleware();

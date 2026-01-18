<?php
/**
 * Bootstrap File for Complex Examples
 * ====================================
 * 
 * This file provides common setup for complex examples, including:
 * - Composer autoloader initialization
 * - Custom autoloader for example helper classes
 * - Timer middleware for performance tracking
 * - Start time tracking for metrics
 * 
 * Used by: example_simple.php, example_complex.php, example_link_check.php,
 *          example_health_check.php, example_cache.php
 */

use Composer\Autoload\ClassLoader;
use Example\GuzzleTimerMiddleware;

// Record start time for performance metrics
$start = microtime(true);

// Load Composer autoloader
// This loads all project dependencies
require __DIR__ . '/../vendor/autoload.php';

// Create custom autoloader for example classes
$loader = new ClassLoader();

// Register the Example namespace
// Maps 'Example' namespace to 'example/lib/Example' directory
$loader->add('Example', __DIR__ . '/lib');

// Activate the autoloader
// Now we can use classes like Example\StatsHandler
$loader->register();

// Create timer middleware for performance tracking
// This will be used to measure HTTP request times
$timerMiddleware = new GuzzleTimerMiddleware();

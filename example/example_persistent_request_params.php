<?php
/**
 * Persistent Request Parameters Example
 * ======================================
 * 
 * This example demonstrates how to add persistent query parameters to all HTTP requests.
 * This is useful when crawling APIs or websites that require authentication tokens,
 * API keys, or tracking parameters on every request.
 * 
 * Key Concepts:
 * - Custom Guzzle client configuration with base_uri and query parameters
 * - Using Guzzle's 'query' option to add parameters to all requests
 * - Request handler customization
 * 
 * Use Cases:
 * - API crawling with authentication tokens (?api_key=xxx)
 * - Tracking parameters (?utm_source=crawler)
 * - Session identifiers (?session_id=xxx)
 * - Any parameters that need to be present on every request
 * 
 * For more Guzzle options, see:
 * https://docs.guzzlephp.org/en/stable/request-options.html
 */

use GuzzleHttp\Client;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Spider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Spider with a base URL
// We'll crawl httpbin.org which echoes back the request details
$spider = new Spider('http://httpbin.org/basic-auth/foo/bar');

// Create a custom request handler
$requestHandler = new GuzzleRequestHandler();

// Configure a Guzzle client with persistent query parameters
// These parameters will be added to EVERY request made by the spider
// The 'query' option accepts an array of key-value pairs
// The 'http_errors' => false prevents exceptions on 4XX/5XX responses
$requestHandler->setClient(new Client([
    'auth' => ['foo', 'bar', 'basic'],          // HTTP Basic authentication
    'http_errors' => false,                      // Don't throw on error responses
    'query' => ['persistent_param' => 'value']   // Add this parameter to all requests
]));

// Set the custom request handler on the spider
$spider->getDownloader()->setRequestHandler($requestHandler);

// Execute the crawl
// All requests will now include the persistent_param query parameter
$spider->crawl();

// Process the downloaded resources
// httpbin.org will echo back the request, allowing us to verify the parameters were sent
echo "\n\nRESPONSE: ";
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    // Display the HTTP status code and reason phrase
    echo "\n" . $resource->getResponse()->getStatusCode() . ": " . $resource->getResponse()->getReasonPhrase();
    
    // Display the response body
    // The response should show that our persistent_param was included in the request
    echo "\n" . $resource->getResponse()->getBody();
}

<?php
/**
 * HTTP Authentication Example
 * ============================
 * 
 * This example demonstrates how to crawl websites that require HTTP authentication.
 * It shows how to configure a custom Guzzle client with authentication credentials.
 * 
 * Key Concepts:
 * - Custom Guzzle client configuration
 * - HTTP Basic Authentication (can be adapted for Digest or NTLM)
 * - Request handler customization
 * - Disabling HTTP error exceptions
 * 
 * Authentication Types Supported by Guzzle:
 * - 'basic'  : HTTP Basic Authentication
 * - 'digest' : HTTP Digest Authentication
 * - 'ntlm'   : NTLM Authentication
 * 
 * For more authentication options, see Guzzle documentation:
 * https://docs.guzzlephp.org/en/stable/request-options.html#auth
 */

use GuzzleHttp\Client;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Spider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Spider with a URL that requires authentication
// httpbin.org provides a test endpoint that requires basic auth
$spider = new Spider('http://httpbin.org/basic-auth/foo/bar');

// Create a custom request handler
$requestHandler = new GuzzleRequestHandler();

// Configure a Guzzle client with authentication credentials
// The 'auth' option is an array: [username, password, auth_type]
// The 'http_errors' => false option prevents Guzzle from throwing exceptions on 4XX/5XX responses
$requestHandler->setClient(new Client(['auth' => ['foo', 'bar', 'basic'], 'http_errors' => false]));

// Set the custom request handler on the spider's downloader
$spider->getDownloader()->setRequestHandler($requestHandler);

// Execute the crawl
// The spider will now send the authentication credentials with every request
$spider->crawl();

// Process the downloaded resources
// With successful authentication, we should see a 200 OK response
echo "\n\nRESPONSE: ";
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    // Display the HTTP status code and reason phrase
    echo "\n" . $resource->getResponse()->getStatusCode() . ": " . $resource->getResponse()->getReasonPhrase();
    
    // Display the response body
    // With authentication, httpbin.org returns JSON with authentication details
    echo "\n" . $resource->getResponse()->getBody();
}

<?php

use GuzzleHttp\Client;
use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Spider;

require_once __DIR__ . '/../vendor/autoload.php';

// Create Spider
$spider = new Spider('http://httpbin.org/basic-auth/foo/bar', null, null, null, null);

$requestHandler = new GuzzleRequestHandler();
// Set a custom Guzzle client that does basic auth. See Guzzle docs on how to use other types of authentication.
$requestHandler->setClient(new Client(['auth' => ['foo', 'bar', 'basic'], 'http_errors' => false]));
$spider->getDownloader()->setRequestHandler($requestHandler);

// Execute crawl
$spider->crawl();

// Finally we could do some processing on the downloaded resources
// In this example, we will echo the title of all resources
echo "\n\nRESPONSE: ";
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    echo "\n" . $resource->getResponse()->getStatusCode() . ": " . $resource->getResponse()->getReasonPhrase();
    echo "\n" . $resource->getResponse()->getBody();
}

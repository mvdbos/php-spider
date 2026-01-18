<?php
/**
 * Link Check Request Handler
 * ===========================
 * 
 * Custom request handler that allows the spider to continue crawling
 * even when encountering HTTP error responses (4XX/5XX).
 * 
 * Key Difference from Default Handler:
 * - Default GuzzleRequestHandler: Throws exceptions on 4XX/5XX responses, stopping the crawl
 * - LinkCheckRequestHandler: Sets 'http_errors' => false, capturing error responses
 * 
 * This is essential for:
 * - Link checking (finding broken links)
 * - Health monitoring (tracking error pages)
 * - Website validation (identifying all problematic pages)
 * 
 * How It Works:
 * The 'http_errors' => false option tells Guzzle to return responses with
 * error status codes instead of throwing exceptions. This allows the spider to:
 * 1. Capture the error response
 * 2. Persist it with its status code
 * 3. Continue crawling other URIs
 * 
 * Usage:
 * ```php
 * $spider = new Spider('https://example.com');
 * $spider->getDownloader()->setRequestHandler(new LinkCheckRequestHandler());
 * $spider->crawl();
 * 
 * // Now you can check status codes of all crawled resources
 * foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
 *     $code = $resource->getResponse()->getStatusCode();
 *     if ($code >= 400) {
 *         echo "Broken link: " . $resource->getUri()->toString() . " ($code)\n";
 *     }
 * }
 * ```
 * 
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace Example;

use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class LinkCheckRequestHandler extends GuzzleRequestHandler
{
    /**
     * Make an HTTP request that doesn't throw on error responses
     * 
     * This overrides the default request() method to set 'http_errors' => false,
     * allowing 4XX and 5XX responses to be captured instead of throwing exceptions.
     * 
     * @param DiscoveredUri $uri The URI to request
     * @return Resource The resource with the response (including error responses)
     */
    public function request(DiscoveredUri $uri): Resource
    {
        // Make GET request with 'http_errors' => false
        // This prevents Guzzle from throwing exceptions on 4XX/5XX responses
        $response = $this->getClient()->get($uri->toString(), ['http_errors' => false]);
        
        // Wrap the response in a Resource object and return it
        return new Resource($uri, $response);
    }
}

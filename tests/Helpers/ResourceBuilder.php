<?php

namespace VDB\Spider\Tests\Helpers;

use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

/**
 * Test builder for creating Resource objects with sensible defaults.
 * Simplifies test setup and makes test intent clearer.
 */
class ResourceBuilder
{
    private string $uri = 'http://example.com';
    private int $depth = 0;
    private int $statusCode = 200;
    private array $headers = [];
    private string $body = '<html><head><title>Test</title></head><body><p>Test content</p></body></html>';

    /**
     * Create a new ResourceBuilder instance
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the URI for the resource
     */
    public function withUri(string $uri): self
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * Set the depth at which this URI was discovered
     */
    public function withDepth(int $depth): self
    {
        $this->depth = $depth;
        return $this;
    }

    /**
     * Set the HTTP status code
     */
    public function withStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Set HTTP response headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * Add a single HTTP response header
     */
    public function withHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set the response body content
     */
    public function withBody(string $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Set an HTML body with the specified title and content
     */
    public function withHtmlContent(string $title, string $content): self
    {
        $this->body = sprintf(
            '<html><head><title>%s</title></head><body>%s</body></html>',
            htmlspecialchars($title),
            $content
        );
        return $this;
    }

    /**
     * Set an HTML body with links
     */
    public function withLinks(array $links): self
    {
        $linkHtml = '';
        foreach ($links as $href => $text) {
            $linkHtml .= sprintf('<a href="%s">%s</a>', htmlspecialchars($href), htmlspecialchars($text));
        }
        $this->body = sprintf(
            '<html><head><title>Test</title></head><body>%s</body></html>',
            $linkHtml
        );
        return $this;
    }

    /**
     * Set JSON body content
     */
    public function withJsonBody(array $data): self
    {
        $this->body = json_encode($data);
        $this->headers['Content-Type'] = 'application/json';
        return $this;
    }

    /**
     * Build and return the Resource object
     */
    public function build(): Resource
    {
        $discoveredUri = new DiscoveredUri(new Uri($this->uri), $this->depth);
        $response = new Response($this->statusCode, $this->headers, $this->body);
        return new Resource($discoveredUri, $response);
    }

    /**
     * Build and return just the DiscoveredUri (without Response/Resource)
     */
    public function buildUri(): DiscoveredUri
    {
        return new DiscoveredUri(new Uri($this->uri), $this->depth);
    }
}

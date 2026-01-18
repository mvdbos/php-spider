<?php

namespace VDB\Spider\Tests\Helpers;

use GuzzleHttp\Psr7\Response;
use RuntimeException;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\QueueManager\InMemoryQueueManager;
use VDB\Spider\Resource;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Spider;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * Test builder for creating Spider instances with mocked dependencies.
 * Simplifies spider test setup and makes test intent clearer.
 */
class SpiderTestBuilder
{
    private string $seed = 'http://example.com';
    private ?DiscovererSet $discovererSet = null;
    private ?InMemoryQueueManager $queueManager = null;
    private ?Downloader $downloader = null;
    private ?string $spiderId = null;
    private array $linkMap = [];
    private ?RequestHandlerInterface $requestHandler = null;

    /**
     * Create a new SpiderTestBuilder
     */
    public function __construct()
    {
    }

    /**
     * Create a new SpiderTestBuilder instance
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the seed URI
     */
    public function withSeed(string $seed): self
    {
        $this->seed = $seed;
        return $this;
    }

    /**
     * Set a custom DiscovererSet
     */
    public function withDiscovererSet(DiscovererSet $discovererSet): self
    {
        $this->discovererSet = $discovererSet;
        return $this;
    }

    /**
     * Set a custom QueueManager
     */
    public function withQueueManager(InMemoryQueueManager $queueManager): self
    {
        $this->queueManager = $queueManager;
        return $this;
    }

    /**
     * Set a custom Downloader
     */
    public function withDownloader(Downloader $downloader): self
    {
        $this->downloader = $downloader;
        return $this;
    }

    /**
     * Set the spider ID
     */
    public function withSpiderId(string $spiderId): self
    {
        $this->spiderId = $spiderId;
        return $this;
    }

    /**
     * Set a map of URIs to response bodies for mocked requests.
     *
     * Example:
     * ->withLinkMap([
     *     'http://example.com' => '<a href="/page1">Link 1</a>',
     *     'http://example.com/page1' => '<a href="/page2">Link 2</a>',
     * ])
     *
     * @param array<string, string|Response> $linkMap URI => body or Response
     */
    public function withLinkMap(array $linkMap): self
    {
        $this->linkMap = $linkMap;
        return $this;
    }

    /**
     * Build and return the Spider with mocked request handler
     */
    public function build(): Spider
    {
        // Create request handler if we have a link map
        if (!empty($this->linkMap)) {
            $this->requestHandler = $this->createMockedRequestHandler();

            if ($this->downloader === null) {
                $this->downloader = new Downloader(null, $this->requestHandler);
            }
        }

        // Create Spider with provided or default components
        $spider = new Spider(
            $this->seed,
            $this->discovererSet,
            $this->queueManager,
            $this->downloader,
            $this->spiderId
        );

        return $spider;
    }

    /**
     * Create a request handler that returns mocked responses
     */
    private function createMockedRequestHandler(): RequestHandlerInterface
    {
        $linkMap = $this->linkMap;

        return new class ($linkMap) implements RequestHandlerInterface {
            private array $linkMap;

            public function __construct(array $linkMap)
            {
                $this->linkMap = $linkMap;
            }

            public function request(DiscoveredUri $uri): Resource
            {
                $uriString = $uri->toString();

                if (!array_key_exists($uriString, $this->linkMap)) {
                    throw new RuntimeException("URI not mocked: $uriString");
                }

                $responseData = $this->linkMap[$uriString];

                // Allow either Response objects or body strings
                if ($responseData instanceof Response) {
                    $response = $responseData;
                } else {
                    $response = new Response(200, [], $responseData);
                }

                return new Resource($uri, $response);
            }
        };
    }

    /**
     * Get the request handler (if created)
     */
    public function getRequestHandler(): ?RequestHandlerInterface
    {
        return $this->requestHandler;
    }
}

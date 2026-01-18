# Extending PHP-Spider

This document provides cookbook-style recipes for common extension scenarios.

## Table of Contents

1. [Custom Discoverers](#custom-discoverers)
2. [Custom Prefetch Filters](#custom-prefetch-filters)
3. [Custom Postfetch Filters](#custom-postfetch-filters)
4. [Custom Persistence Handlers](#custom-persistence-handlers)
5. [Custom Request Handlers](#custom-request-handlers)
6. [Event Subscribers](#event-subscribers)
7. [Custom Queue Managers](#custom-queue-managers)

---

## Custom Discoverers

Discoverers extract URIs from downloaded resources. Implement `DiscovererInterface`.

### Example: JSON API Link Discoverer

Extract links from JSON API responses:

```php
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

class JsonApiDiscoverer implements DiscovererInterface
{
    private string $jsonPointer;
    
    public function __construct(string $jsonPointer = '/links')
    {
        $this->jsonPointer = $jsonPointer;
    }
    
    public function getName(): string
    {
        return 'json-api-discoverer';
    }
    
    public function discover(Resource $resource): array
    {
        $contentType = $resource->getResponse()->getHeaderLine('Content-Type');
        
        // Only process JSON responses
        if (strpos($contentType, 'application/json') === false) {
            return [];
        }
        
        $body = $resource->getResponse()->getBody()->__toString();
        $data = json_decode($body, true);
        
        if (!$data) {
            return [];
        }
        
        // Extract links from JSON structure
        $links = $this->extractLinksFromJson($data, $this->jsonPointer);
        
        // Convert to DiscoveredUri objects
        $currentDepth = $resource->getUri()->getDepthFound();
        $discoveredUris = [];
        
        foreach ($links as $link) {
            try {
                $uri = new Uri($link);
                $discoveredUris[] = new DiscoveredUri($uri, $currentDepth + 1);
            } catch (\Exception $e) {
                // Skip invalid URIs
                continue;
            }
        }
        
        return $discoveredUris;
    }
    
    private function extractLinksFromJson(array $data, string $pointer): array
    {
        // Simple implementation - traverse JSON structure
        $parts = array_filter(explode('/', $pointer));
        $current = $data;
        
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                return [];
            }
            $current = $current[$part];
        }
        
        return is_array($current) ? $current : [$current];
    }
}
```

**Usage:**
```php
$spider->getDiscovererSet()->addDiscoverer(new JsonApiDiscoverer('/data/next_page'));
```

### Example: Sitemap.xml Discoverer

Parse sitemap XML files:

```php
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

class SitemapDiscoverer implements DiscovererInterface
{
    public function getName(): string
    {
        return 'sitemap-discoverer';
    }
    
    public function discover(Resource $resource): array
    {
        $body = $resource->getResponse()->getBody()->__toString();
        
        try {
            $xml = new \SimpleXMLElement($body);
            $xml->registerXPathNamespace('sm', 'http://www.sitemaps.org/schemas/sitemap/0.9');
            
            $urls = $xml->xpath('//sm:url/sm:loc');
            
            $currentDepth = $resource->getUri()->getDepthFound();
            $discoveredUris = [];
            
            foreach ($urls as $url) {
                $uri = new Uri((string)$url);
                $discoveredUris[] = new DiscoveredUri($uri, $currentDepth + 1);
            }
            
            return $discoveredUris;
        } catch (\Exception $e) {
            return [];
        }
    }
}
```

---

## Custom Prefetch Filters

Prefetch filters decide which URIs to skip **before** downloading. Implement `PreFetchFilterInterface`.

### Example: File Extension Filter

Skip URIs with specific file extensions:

```php
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

class FileExtensionFilter implements PreFetchFilterInterface
{
    private array $excludedExtensions;
    
    public function __construct(array $excludedExtensions)
    {
        // Normalize extensions (lowercase, no dot)
        $this->excludedExtensions = array_map(
            fn($ext) => strtolower(ltrim($ext, '.')),
            $excludedExtensions
        );
    }
    
    public function match(UriInterface $uri): bool
    {
        $path = $uri->getPath();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        
        // Return true to exclude
        return in_array($extension, $this->excludedExtensions);
    }
}
```

**Usage:**
```php
// Skip images, videos, and documents
$spider->getDiscovererSet()->addFilter(
    new FileExtensionFilter(['jpg', 'png', 'gif', 'mp4', 'pdf', 'zip'])
);
```

### Example: URL Pattern Filter

Skip URIs matching specific patterns:

```php
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

class UrlPatternFilter implements PreFetchFilterInterface
{
    private array $excludePatterns;
    
    public function __construct(array $excludePatterns)
    {
        $this->excludePatterns = $excludePatterns;
    }
    
    public function match(UriInterface $uri): bool
    {
        $url = $uri->toString();
        
        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true; // Exclude this URI
            }
        }
        
        return false; // Don't exclude
    }
}
```

**Usage:**
```php
// Skip admin pages, login pages, and API endpoints
$spider->getDiscovererSet()->addFilter(
    new UrlPatternFilter([
        '#/admin/#i',
        '#/login#i',
        '#/api/#i',
        '#\?sort=#', // Skip pages with sort query params
    ])
);
```

### Example: Max Depth Per Domain Filter

Different max depths for different domains:

```php
use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

class DomainDepthFilter implements PreFetchFilterInterface
{
    private array $domainDepths;
    private int $defaultMaxDepth;
    
    public function __construct(array $domainDepths, int $defaultMaxDepth = 3)
    {
        $this->domainDepths = $domainDepths;
        $this->defaultMaxDepth = $defaultMaxDepth;
    }
    
    public function match(UriInterface $uri): bool
    {
        $host = $uri->getHost();
        $maxDepth = $this->domainDepths[$host] ?? $this->defaultMaxDepth;
        
        // DiscoveredUri has depth tracking
        if ($uri instanceof \VDB\Spider\Uri\DiscoveredUri) {
            return $uri->getDepthFound() > $maxDepth;
        }
        
        return false;
    }
}
```

**Usage:**
```php
$spider->getDiscovererSet()->addFilter(
    new DomainDepthFilter([
        'example.com' => 5,      // Deeper crawl for main site
        'blog.example.com' => 2, // Shallow for blog
    ], 3) // Default for others
);
```

---

## Custom Postfetch Filters

Postfetch filters decide which resources to skip **after** downloading. Implement `PostFetchFilterInterface`.

### Example: Content Length Filter

Skip resources that are too large or too small:

```php
use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource;

class ContentLengthFilter implements PostFetchFilterInterface
{
    private ?int $minBytes;
    private ?int $maxBytes;
    
    public function __construct(?int $minBytes = null, ?int $maxBytes = null)
    {
        $this->minBytes = $minBytes;
        $this->maxBytes = $maxBytes;
    }
    
    public function match(Resource $resource): bool
    {
        $contentLength = $resource->getResponse()->getHeaderLine('Content-Length');
        
        if (!$contentLength) {
            // If no header, check actual body size
            $contentLength = strlen($resource->getResponse()->getBody()->__toString());
        }
        
        $length = (int)$contentLength;
        
        if ($this->minBytes !== null && $length < $this->minBytes) {
            return true; // Too small, exclude
        }
        
        if ($this->maxBytes !== null && $length > $this->maxBytes) {
            return true; // Too large, exclude
        }
        
        return false;
    }
}
```

**Usage:**
```php
// Only persist resources between 1KB and 5MB
$spider->getDownloader()->addPostFetchFilter(
    new ContentLengthFilter(1024, 5 * 1024 * 1024)
);
```

### Example: Content Quality Filter

Filter out pages with low content quality:

```php
use VDB\Spider\Filter\PostFetchFilterInterface;
use VDB\Spider\Resource;

class ContentQualityFilter implements PostFetchFilterInterface
{
    private int $minWordCount;
    
    public function __construct(int $minWordCount = 100)
    {
        $this->minWordCount = $minWordCount;
    }
    
    public function match(Resource $resource): bool
    {
        $contentType = $resource->getResponse()->getHeaderLine('Content-Type');
        
        // Only check HTML content
        if (strpos($contentType, 'text/html') === false) {
            return false;
        }
        
        try {
            // Extract text content
            $text = $resource->getCrawler()
                ->filter('body')
                ->text();
            
            // Count words
            $wordCount = str_word_count($text);
            
            // Exclude if too few words
            return $wordCount < $this->minWordCount;
        } catch (\Exception $e) {
            // If parsing fails, don't exclude
            return false;
        }
    }
}
```

**Usage:**
```php
// Skip pages with less than 200 words
$spider->getDownloader()->addPostFetchFilter(
    new ContentQualityFilter(200)
);
```

---

## Custom Persistence Handlers

Persistence handlers determine how resources are stored. Implement `PersistenceHandlerInterface`.

### Example: Database Persistence Handler

Store resources in a database:

```php
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\Resource;

class DatabasePersistenceHandler implements PersistenceHandlerInterface, \Iterator, \Countable
{
    private \PDO $pdo;
    private string $spiderId;
    private array $cache = [];
    private int $position = 0;
    
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    public function setSpiderId(string $spiderId): void
    {
        $this->spiderId = $spiderId;
    }
    
    public function persist(Resource $resource): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO crawled_resources (spider_id, url, status_code, headers, body, crawled_at) 
             VALUES (?, ?, ?, ?, ?, NOW())'
        );
        
        $stmt->execute([
            $this->spiderId,
            $resource->getUri()->toString(),
            $resource->getResponse()->getStatusCode(),
            json_encode($resource->getResponse()->getHeaders()),
            $resource->getResponse()->getBody()->__toString(),
        ]);
        
        // Update cache
        $this->cache[] = $resource;
    }
    
    public function count(): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM crawled_resources WHERE spider_id = ?'
        );
        $stmt->execute([$this->spiderId]);
        return (int)$stmt->fetchColumn();
    }
    
    // Iterator implementation
    public function current(): Resource
    {
        return $this->cache[$this->position];
    }
    
    public function key(): int
    {
        return $this->position;
    }
    
    public function next(): void
    {
        ++$this->position;
    }
    
    public function rewind(): void
    {
        // Load all resources for this spider
        $stmt = $this->pdo->prepare(
            'SELECT * FROM crawled_resources WHERE spider_id = ?'
        );
        $stmt->execute([$this->spiderId]);
        
        $this->cache = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            // Reconstruct Resource objects
            // (Simplified - real implementation would need proper reconstruction)
            $this->cache[] = $row;
        }
        
        $this->position = 0;
    }
    
    public function valid(): bool
    {
        return isset($this->cache[$this->position]);
    }
}
```

### Example: CSV Export Handler

Export crawl results to CSV:

```php
use VDB\Spider\PersistenceHandler\PersistenceHandlerInterface;
use VDB\Spider\Resource;

class CsvPersistenceHandler implements PersistenceHandlerInterface, \Iterator, \Countable
{
    private string $filePath;
    private $fileHandle;
    private array $resources = [];
    private array $columns;
    
    public function __construct(string $filePath, array $columns = ['url', 'status', 'title'])
    {
        $this->filePath = $filePath;
        $this->columns = $columns;
    }
    
    public function setSpiderId(string $spiderId): void
    {
        $this->filePath = str_replace('{spider_id}', $spiderId, $this->filePath);
        $this->fileHandle = fopen($this->filePath, 'w');
        
        // Write header
        fputcsv($this->fileHandle, $this->columns);
    }
    
    public function persist(Resource $resource): void
    {
        $row = [];
        
        foreach ($this->columns as $column) {
            $row[] = match($column) {
                'url' => $resource->getUri()->toString(),
                'status' => $resource->getResponse()->getStatusCode(),
                'title' => $this->extractTitle($resource),
                'content_type' => $resource->getResponse()->getHeaderLine('Content-Type'),
                'content_length' => strlen($resource->getResponse()->getBody()->__toString()),
                default => '',
            };
        }
        
        fputcsv($this->fileHandle, $row);
        $this->resources[] = $resource;
    }
    
    private function extractTitle(Resource $resource): string
    {
        try {
            return $resource->getCrawler()->filter('title')->text();
        } catch (\Exception $e) {
            return '';
        }
    }
    
    public function count(): int
    {
        return count($this->resources);
    }
    
    public function __destruct()
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
        }
    }
    
    // Iterator implementation (return stored resources)
    public function current(): Resource { return current($this->resources); }
    public function key(): int { return key($this->resources); }
    public function next(): void { next($this->resources); }
    public function rewind(): void { reset($this->resources); }
    public function valid(): bool { return current($this->resources) !== false; }
}
```

**Usage:**
```php
$spider->getDownloader()->setPersistenceHandler(
    new CsvPersistenceHandler(
        '/path/to/results-{spider_id}.csv',
        ['url', 'status', 'title', 'content_type']
    )
);
```

---

## Custom Request Handlers

Request handlers control how HTTP requests are made. Implement `RequestHandlerInterface`.

### Example: Proxy Request Handler

Route requests through a proxy:

```php
use GuzzleHttp\Client;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class ProxyRequestHandler implements RequestHandlerInterface
{
    private Client $client;
    
    public function __construct(string $proxyUrl, array $guzzleOptions = [])
    {
        $options = array_merge([
            'proxy' => $proxyUrl,
            'timeout' => 30,
            'verify' => false, // May need to disable SSL verification with some proxies
        ], $guzzleOptions);
        
        $this->client = new Client($options);
    }
    
    public function request(DiscoveredUri $uri): Resource
    {
        $response = $this->client->get($uri->toString());
        return new Resource($uri, $response);
    }
}
```

**Usage:**
```php
use VDB\Spider\Downloader\Downloader;

$requestHandler = new ProxyRequestHandler('http://proxy.example.com:8080');
$downloader = new Downloader(null, $requestHandler);
$spider->setDownloader($downloader);
```

### Example: Authenticated Request Handler

Add authentication headers to all requests:

```php
use GuzzleHttp\Client;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class AuthenticatedRequestHandler implements RequestHandlerInterface
{
    private Client $client;
    private array $headers;
    
    public function __construct(string $token, string $tokenType = 'Bearer')
    {
        $this->headers = [
            'Authorization' => "$tokenType $token"
        ];
        
        $this->client = new Client([
            'headers' => $this->headers
        ]);
    }
    
    public function request(DiscoveredUri $uri): Resource
    {
        $response = $this->client->get($uri->toString());
        return new Resource($uri, $response);
    }
}
```

---

## Event Subscribers

Event subscribers allow you to react to crawl events. Implement Symfony's `EventSubscriberInterface`.

### Example: Statistics Collector

Track crawl statistics:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;

class CrawlStatsSubscriber implements EventSubscriberInterface
{
    private array $stats = [
        'urls_queued' => 0,
        'urls_crawled' => 0,
        'urls_failed' => 0,
        'start_time' => null,
        'end_time' => null,
    ];
    
    public static function getSubscribedEvents(): array
    {
        return [
            SpiderEvents::SPIDER_CRAWL_PRE_CRAWL => 'onPreCrawl',
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE => 'onPostEnqueue',
            SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED => 'onResourcePersisted',
            SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST => 'onErrorRequest',
        ];
    }
    
    public function onPreCrawl(GenericEvent $event): void
    {
        $this->stats['start_time'] = microtime(true);
    }
    
    public function onPostEnqueue(GenericEvent $event): void
    {
        $this->stats['urls_queued']++;
    }
    
    public function onResourcePersisted(GenericEvent $event): void
    {
        $this->stats['urls_crawled']++;
    }
    
    public function onErrorRequest(GenericEvent $event): void
    {
        $this->stats['urls_failed']++;
    }
    
    public function getStats(): array
    {
        $this->stats['end_time'] = microtime(true);
        $this->stats['duration'] = $this->stats['end_time'] - $this->stats['start_time'];
        return $this->stats;
    }
}
```

**Usage:**
```php
$statsSubscriber = new CrawlStatsSubscriber();
$spider->getDispatcher()->addSubscriber($statsSubscriber);
$spider->getQueueManager()->getDispatcher()->addSubscriber($statsSubscriber);
$spider->getDownloader()->getDispatcher()->addSubscriber($statsSubscriber);

$spider->crawl();

print_r($statsSubscriber->getStats());
```

### Example: Progress Reporter

Show real-time crawl progress:

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Spider\Event\SpiderEvents;

class ProgressReporter implements EventSubscriberInterface
{
    private int $crawled = 0;
    private int $queued = 0;
    private int $failed = 0;
    
    public static function getSubscribedEvents(): array
    {
        return [
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE => 'onEnqueue',
            SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED => 'onPersisted',
            SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST => 'onError',
        ];
    }
    
    public function onEnqueue(GenericEvent $event): void
    {
        $this->queued++;
        $this->updateDisplay();
    }
    
    public function onPersisted(GenericEvent $event): void
    {
        $this->crawled++;
        $this->updateDisplay();
    }
    
    public function onError(GenericEvent $event): void
    {
        $this->failed++;
        $this->updateDisplay();
    }
    
    private function updateDisplay(): void
    {
        // Clear line and rewrite
        echo "\r" . str_repeat(' ', 80) . "\r";
        echo sprintf(
            "Queued: %d | Crawled: %d | Failed: %d",
            $this->queued,
            $this->crawled,
            $this->failed
        );
    }
}
```

---

## Custom Queue Managers

Queue managers control URI traversal order. Implement `QueueManagerInterface`.

### Example: Priority Queue Manager

Prioritize certain URIs over others:

```php
use VDB\Spider\QueueManager\QueueManagerInterface;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Event\DispatcherTrait;
use VDB\Spider\Event\SpiderEvents;
use Symfony\Component\EventDispatcher\GenericEvent;

class PriorityQueueManager implements QueueManagerInterface
{
    use DispatcherTrait;
    
    private \SplPriorityQueue $queue;
    private int $insertionOrder = 0;
    
    public function __construct()
    {
        $this->queue = new \SplPriorityQueue();
    }
    
    public function addUri(DiscoveredUri $uri): void
    {
        $priority = $this->calculatePriority($uri);
        
        // Use insertion order as secondary priority for stable sort
        $this->queue->insert($uri, [$priority, $this->insertionOrder++]);
        
        $this->getDispatcher()->dispatch(
            new GenericEvent($this, ['uri' => $uri]),
            SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE
        );
    }
    
    public function next(): ?DiscoveredUri
    {
        if ($this->queue->isEmpty()) {
            return null;
        }
        
        return $this->queue->extract();
    }
    
    private function calculatePriority(DiscoveredUri $uri): int
    {
        $priority = 100;
        
        // Higher priority for shallower depth
        $priority -= $uri->getDepthFound() * 10;
        
        // Higher priority for certain paths
        if (strpos($uri->getPath(), '/blog/') !== false) {
            $priority += 20;
        }
        
        if (strpos($uri->getPath(), '/archive/') !== false) {
            $priority -= 30;
        }
        
        return $priority;
    }
}
```

**Usage:**
```php
$spider->setQueueManager(new PriorityQueueManager());
```

---

## Combining Extensions

You can combine multiple extensions for powerful customization:

```php
use VDB\Spider\Spider;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;

// Create spider
$spider = new Spider('https://example.com');

// Add multiple discoverers
$spider->getDiscovererSet()->addDiscoverer(new XPathExpressionDiscoverer("//a"));
$spider->getDiscovererSet()->addDiscoverer(new JsonApiDiscoverer('/links'));

// Add multiple prefetch filters
$spider->getDiscovererSet()->addFilter(new FileExtensionFilter(['jpg', 'png', 'pdf']));
$spider->getDiscovererSet()->addFilter(new UrlPatternFilter(['#/admin/#', '#/login#']));
$spider->getDiscovererSet()->addFilter(new DomainDepthFilter(['example.com' => 5]));

// Add postfetch filters
$spider->getDownloader()->addPostFetchFilter(new ContentLengthFilter(1024, 5 * 1024 * 1024));
$spider->getDownloader()->addPostFetchFilter(new ContentQualityFilter(200));

// Use custom persistence
$spider->getDownloader()->setPersistenceHandler(
    new CsvPersistenceHandler('/path/to/results.csv', ['url', 'status', 'title'])
);

// Add event subscribers
$statsSubscriber = new CrawlStatsSubscriber();
$progressReporter = new ProgressReporter();
$spider->getDispatcher()->addSubscriber($statsSubscriber);
$spider->getQueueManager()->getDispatcher()->addSubscriber($statsSubscriber);
$spider->getDownloader()->getDispatcher()->addSubscriber($progressReporter);

// Crawl
$spider->crawl();

// Report
print_r($statsSubscriber->getStats());
```

---

## Tips for Extension Development

1. **Start Simple**: Begin with the built-in implementations and extend from there.

2. **Test in Isolation**: Test your custom components independently before integrating.

3. **Use Events for Cross-Cutting Concerns**: Logging, monitoring, throttling are best handled via events.

4. **Filters for Conditional Logic**: Use filters to decide what to crawl/save, not event listeners.

5. **Check Existing Extensions**: Look at built-in filters and handlers for patterns.

6. **Document Your Extensions**: Future you (and others) will appreciate clear documentation.

7. **Follow PSR Standards**: Match the coding style of the project (PSR-1, PSR-2).

8. **Handle Errors Gracefully**: Don't let your extension crash the entire crawl.

---

For more information:
- [Architecture Documentation](architecture.md)
- [Lifecycle Documentation](lifecycle.md)
- [Example Scripts](../example/)

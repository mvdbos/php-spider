# PHP-Spider Architecture

This document explains the core architecture of PHP-Spider, how components interact, and the flow of data and events through the system.

## Component Overview

PHP-Spider is built around five core components that work together to crawl websites:

```
┌─────────────────────────────────────────────────────────────────┐
│                            Spider                                │
│  (Orchestrates the crawl process and manages components)         │
└────────────┬────────────────────────────────────────────────────┘
             │
             ├──────────────┬──────────────┬──────────────┐
             │              │              │              │
             ▼              ▼              ▼              ▼
    ┌────────────┐  ┌─────────────┐  ┌──────────┐  ┌──────────┐
    │Discoverer  │  │Queue        │  │Downloader│  │Persistence│
    │Set         │  │Manager      │  │          │  │Handler   │
    └────────────┘  └─────────────┘  └──────────┘  └──────────┘
         │                 │              │              │
         │                 │              │              │
         ▼                 ▼              ▼              ▼
    Find URIs        Track URIs      Fetch pages    Save results
    in content       to visit        via HTTP       to storage
```

### 1. Spider (src/Spider.php)

The main orchestrator that coordinates all components. Responsibilities:
- Initializes the crawl with a seed URL
- Manages the crawl loop (fetch → discover → queue → repeat)
- Dispatches lifecycle events
- Handles signals (SIGTERM, SIGINT) for graceful shutdown

**Key Methods:**
- `crawl()` - Starts the crawl process
- `getDiscovererSet()` - Access discoverers and filters
- `getDownloader()` - Access download and persistence configuration
- `getQueueManager()` - Access queue and traversal settings

### 2. DiscovererSet (src/Discoverer/DiscovererSet.php)

Manages link discovery and filtering. Responsibilities:
- Holds collection of discoverers (XPath, CSS selectors, etc.)
- Applies prefetch filters before downloading
- Tracks visited URIs to prevent duplicates
- Enforces max depth limit

**Key Properties:**
- `$maxDepth` (default: 3) - How deep to crawl from seed
- `$discoverers` - Collection of discoverer implementations
- `$filters` - Collection of prefetch filters
- `$alreadySeenUris` - De-duplication cache

### 3. QueueManager (src/QueueManager/InMemoryQueueManager.php)

Manages the queue of URIs to visit. Responsibilities:
- Stores URIs to be crawled
- Provides next URI based on traversal algorithm
- Enforces max queue size limit
- Dispatches queue-related events

**Traversal Algorithms:**
- **Depth-First** (default): Goes deep before wide (like exploring a tree branch fully before moving to the next)
- **Breadth-First**: Visits all links at one level before going deeper

**Key Properties:**
- `$maxQueueSize` (default: 0 = unlimited) - Maximum URIs to queue
- `$traversalAlgorithm` - Which algorithm to use

### 4. Downloader (src/Downloader/Downloader.php)

Handles HTTP requests and resource filtering. Responsibilities:
- Fetches resources via RequestHandler (Guzzle by default)
- Applies postfetch filters after downloading
- Enforces download limit
- Dispatches download-related events

**Key Properties:**
- `$downloadLimit` (default: 0 = unlimited) - Max resources to download
- `$requestHandler` - HTTP client wrapper (GuzzleRequestHandler)
- `$persistenceHandler` - Where to save resources
- `$postFetchFilters` - Filters applied after download

### 5. PersistenceHandler (src/PersistenceHandler/)

Stores downloaded resources. Implementations:
- **MemoryPersistenceHandler** - In-memory (default, for small crawls)
- **FileSerializedResourcePersistenceHandler** - File system (for larger crawls)
- **JsonHealthCheckPersistenceHandler** - JSON output for health checks

## Data Flow

### Crawl Loop Sequence

```
1. Spider.crawl()
   ↓
2. Queue seed URI
   ↓
3. Dispatch SPIDER_CRAWL_PRE_CRAWL event
   ↓
4. doCrawl() loop starts:
   │
   ├─→ Get next URI from QueueManager
   │   ↓
   ├─→ Dispatch SPIDER_CRAWL_PRE_REQUEST event
   │   ↓
   ├─→ Downloader.download(uri)
   │   │
   │   ├─→ RequestHandler fetches resource
   │   │   ↓
   │   ├─→ Apply postfetch filters
   │   │   ↓
   │   └─→ PersistenceHandler.persist(resource)
   │       ↓
   ├─→ Dispatch SPIDER_CRAWL_RESOURCE_PERSISTED event
   │   ↓
   ├─→ DiscovererSet.discover(resource)
   │   │
   │   ├─→ Each discoverer finds URIs in content
   │   │   ↓
   │   ├─→ Normalize URIs
   │   │   ↓
   │   ├─→ Remove duplicates
   │   │   ↓
   │   ├─→ Filter out already-seen URIs
   │   │   ↓
   │   ├─→ Apply prefetch filters
   │   │   ↓
   │   └─→ Check max depth
   │       ↓
   ├─→ Add discovered URIs to queue
   │   ↓
   ├─→ Dispatch SPIDER_CRAWL_POST_ENQUEUE event (per URI)
   │   ↓
   └─→ Repeat until queue empty or limits reached
```

## Event System

PHP-Spider uses Symfony's EventDispatcher to provide extension points. Each major component has its own dispatcher.

### Event Flow Across Components

```
Component       Event Name                          When Fired
─────────────────────────────────────────────────────────────────────
Spider          SPIDER_CRAWL_PRE_CRAWL             Before crawl starts
                SPIDER_CRAWL_RESOURCE_PERSISTED    After resource saved
                SPIDER_CRAWL_USER_STOPPED          On signal (SIGTERM, SIGINT)

Downloader      SPIDER_CRAWL_PRE_REQUEST           Before HTTP request
                SPIDER_CRAWL_POST_REQUEST          After HTTP request
                SPIDER_CRAWL_ERROR_REQUEST         On request error
                SPIDER_CRAWL_FILTER_POSTFETCH      When postfetch filter matches

QueueManager    SPIDER_CRAWL_POST_ENQUEUE          When URI added to queue
```

### Event Access Pattern

Each component creates its own EventDispatcher (via `DispatcherTrait`). To add listeners:

```php
// Listen to Spider events
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_CRAWL,
    function($event) { /* ... */ }
);

// Listen to Downloader events (e.g., for politeness policy)
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
    function($event) { /* ... */ }
);

// Listen to QueueManager events (e.g., for statistics)
$spider->getQueueManager()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE,
    function($event) { /* ... */ }
);
```

## Filter System

Filters allow you to control which URIs are crawled and which resources are persisted.

### Prefetch Filters (src/Filter/Prefetch/)

Applied **before** downloading. Return `true` to **exclude** the URI.

**Built-in filters:**
- `AllowedHostsFilter` - Restrict to specific domains
- `AllowedSchemeFilter` - Only http/https
- `AllowedPortsFilter` - Only specific ports
- `RestrictToBaseUriFilter` - Only URIs under seed path
- `UriFilter` - Match URIs against regex pattern
- `UriWithHashFragmentFilter` - Skip URIs with #fragments
- `UriWithQueryStringFilter` - Skip URIs with query strings
- `RobotsTxtDisallowFilter` - Respect robots.txt
- `CachedResourceFilter` - Skip recently cached resources

**Usage:**
```php
$spider->getDiscovererSet()->addFilter(
    new AllowedHostsFilter(['example.com'], $allowSubDomains = true)
);
```

### Postfetch Filters (src/Filter/Postfetch/)

Applied **after** downloading but **before** persistence. Return `true` to **exclude** the resource.

**Built-in filters:**
- `MimeTypeFilter` - Filter by content type
- `ExternalRedirectFilter` - Filter external redirects

**Usage:**
```php
$spider->getDownloader()->addPostFetchFilter(
    new MimeTypeFilter(['text/html', 'application/xhtml+xml'])
);
```

## URI Model

### DiscoveredUri (src/Uri/DiscoveredUri.php)

Wrapper around `vdb/uri` that adds crawl depth tracking.

**Key properties:**
- `$decorated` - The underlying UriInterface implementation
- `$depthFound` - Integer depth where this URI was discovered

Depth tracking enables:
- Max depth enforcement (prevents infinite crawls)
- Understanding crawl breadth vs depth
- Prioritization strategies

**Normalization:**
URIs are normalized to prevent duplicate crawling of equivalent URLs:
- Trailing slashes standardized
- Default ports removed (80 for http, 443 for https)
- Query parameters sorted (if not filtered out)

## Resource Model

### Resource (src/Resource.php)

Represents a downloaded web resource.

**Key properties:**
- `$uri` - The DiscoveredUri that was fetched
- `$response` - PSR-7 ResponseInterface from Guzzle
- `$crawler` - Lazily-loaded Symfony DomCrawler

**Lazy Loading:**
The Symfony DomCrawler is created on first access via `getCrawler()`. This saves memory for resources that are persisted but never parsed.

**Serialization:**
Resources can be serialized for file-based persistence. The `__sleep()` and `__wakeup()` methods handle PSR-7 response serialization manually because streams don't serialize automatically.

## Configuration Patterns

### Basic Crawl Setup

```php
use VDB\Spider\Spider;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;

$spider = new Spider('https://example.com');

// Add discoverer to find links
$spider->getDiscovererSet()->set(
    new XPathExpressionDiscoverer("//a[@href]")
);

// Configure limits
$spider->getDiscovererSet()->maxDepth = 2;
$spider->getQueueManager()->maxQueueSize = 100;
$spider->getDownloader()->setDownloadLimit(50);

// Start crawling
$spider->crawl();
```

### Adding Filters

```php
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\UriWithHashFragmentFilter;

// Restrict to domain
$spider->getDiscovererSet()->addFilter(
    new AllowedHostsFilter(['example.com'], $allowSubDomains = false)
);

// Skip URIs with fragments
$spider->getDiscovererSet()->addFilter(
    new UriWithHashFragmentFilter()
);
```

### File-based Persistence

```php
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;

$spider->getDownloader()->setPersistenceHandler(
    new FileSerializedResourcePersistenceHandler(__DIR__ . '/results')
);
```

### Event Listeners

```php
use VDB\Spider\Event\SpiderEvents;
use VDB\Spider\EventListener\PolitenessPolicyListener;

// Add politeness delay between requests
$politenessPolicyListener = new PolitenessPolicyListener(100); // 100ms
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
    [$politenessPolicyListener, 'onCrawlPreRequest']
);
```

## Design Principles

### 1. Component Independence
Each component (QueueManager, Downloader, DiscovererSet) can be instantiated and used independently. This allows:
- Unit testing in isolation
- Swapping implementations (e.g., Redis-backed queue)
- Reusing components in different contexts

### 2. Event-Driven Extensibility
Rather than inheritance, extension is achieved through:
- Event listeners for cross-cutting concerns (logging, stats, throttling)
- Filters for conditional logic (what to crawl, what to save)
- Strategy pattern for algorithms (traversal, persistence)

### 3. Sensible Defaults
The Spider constructor takes optional parameters with defaults:
- Default QueueManager: InMemoryQueueManager (depth-first)
- Default Downloader: with GuzzleRequestHandler and MemoryPersistenceHandler
- Default DiscovererSet: empty (must add discoverers)

This makes simple use cases simple, while allowing full customization.

### 4. Lazy Loading
Expensive operations are deferred until needed:
- DomCrawler created only when accessed
- EventDispatcher created only when first event fires
- Normalization happens once, cached in DiscoveredUri

## Common Pitfalls

### 1. Forgetting to Add Discoverers
Without discoverers, the spider only fetches the seed URL and stops.

**Solution:** Always add at least one discoverer.

### 2. Event Listener on Wrong Dispatcher
The Spider, Downloader, and QueueManager each have separate dispatchers.

**Solution:** Attach listeners to the correct component's dispatcher.

### 3. Filter Logic Confusion
Prefetch filters return `true` to **exclude** (skip) a URI.

**Solution:** Remember the inverted logic or check filter tests for examples.

### 4. Max Depth = 0 Means Only Seed
Depth 0 = seed only, depth 1 = seed + links on seed page, etc.

**Solution:** Set `maxDepth` appropriately for your use case (3 is the default).

### 5. Infinite Crawls
Without filters or limits, the spider can crawl indefinitely.

**Solution:** Always set at least one of:
- `maxDepth`
- `maxQueueSize`
- `downloadLimit`
- Host filter

## Performance Considerations

### Memory Usage
- **MemoryPersistenceHandler**: Stores all resources in RAM. Use for small crawls (<1000 pages).
- **FileSerializedResourcePersistenceHandler**: Writes to disk. Use for larger crawls.

### Network Politeness
- Use `PolitenessPolicyListener` to delay requests to the same domain
- Respect `robots.txt` with `RobotsTxtDisallowFilter`
- Set reasonable `downloadLimit` when testing

### Depth vs Breadth Trade-offs
- **Depth-first**: Uses less memory (smaller queue), but may miss wide pages
- **Breadth-first**: Explores horizontally first, but queue grows larger

## Extension Points

See [extending.md](extending.md) for detailed examples of:
- Custom discoverers
- Custom filters (prefetch and postfetch)
- Custom persistence handlers
- Custom request handlers (for proxies, authentication, etc.)
- Event subscribers for monitoring and logging

## Further Reading

- [Lifecycle Documentation](lifecycle.md) - Step-by-step execution flow
- [Extending PHP-Spider](extending.md) - Cookbook for common customizations
- [Example Scripts](../example/) - Real-world usage examples

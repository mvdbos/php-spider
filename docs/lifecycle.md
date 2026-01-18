# Spider Lifecycle

This document provides a step-by-step walkthrough of the PHP-Spider execution flow, from initialization through crawl completion.

## Initialization Phase

### 1. Spider Construction

```php
$spider = new Spider('https://example.com');
```

**What happens:**
1. `setSeed()` validates and normalizes the seed URL
2. Creates a `DiscoveredUri` at depth 0 for the seed
3. Generates or accepts a `spiderId` (used for persistence)
4. Initializes default components if not provided:
   - `DiscovererSet` (empty by default)
   - `InMemoryQueueManager` (depth-first traversal)
   - `Downloader` (with `GuzzleRequestHandler` and `MemoryPersistenceHandler`)
5. Sets up signal handlers (SIGTERM, SIGINT, etc.) if running in CLI

**Code path:**
```
Spider::__construct()
├─→ setSeed($uri)
│   ├─→ Validate URI
│   ├─→ Create Http URI object
│   ├─→ Normalize URI
│   └─→ Wrap in DiscoveredUri at depth 0
├─→ setSpiderId($id)
├─→ setDiscovererSet($set)
├─→ setQueueManager($manager)
└─→ setDownloader($downloader)
```

### 2. Configuration

Users typically configure the spider before crawling:

```php
// Add discoverers
$spider->getDiscovererSet()->addDiscoverer(new XPathExpressionDiscoverer("//a"));

// Set limits
$spider->getDiscovererSet()->setMaxDepth(3);
$spider->getQueueManager()->setMaxQueueSize(1000);
$spider->getDownloader()->setDownloadLimit(500);

// Add filters
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(['example.com']));
$spider->getDownloader()->addPostFetchFilter(new MimeTypeFilter(['text/html']));

// Add event listeners
$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_PRE_CRAWL,
    function($event) { echo "Starting crawl...\n"; }
);
```

---

## Crawl Phase

### 3. Crawl Initiation

```php
$spider->crawl();
```

**What happens:**

#### 3.1 Queue Seed URI
```
Spider::crawl()
└─→ QueueManager::addUri($seed)
    ├─→ Check maxQueueSize limit
    ├─→ Add URI to traversal queue
    ├─→ Increment currentQueueSize
    └─→ Dispatch SPIDER_CRAWL_POST_ENQUEUE event
```

#### 3.2 Configure Persistence Handler
```
Spider::crawl()
└─→ PersistenceHandler::setSpiderId($spiderId)
    └─→ Handler configures storage location/namespace
```

#### 3.3 Dispatch Pre-Crawl Event
```
Spider::crawl()
└─→ Dispatch SPIDER_CRAWL_PRE_CRAWL event
    ├─→ Event contains: spider instance, seed URI
    └─→ Listeners can perform setup (logging, timer start, etc.)
```

#### 3.4 Start Crawl Loop
```
Spider::crawl()
└─→ doCrawl()
```

---

### 4. Main Crawl Loop (doCrawl)

The crawl loop continues until the queue is empty or a limit is reached.

```
┌─────────────────────────────────────┐
│    while (queue has URIs)           │
│         AND                          │
│    (download limit not exceeded)    │
└─────────────────────────────────────┘
          │
          ▼
    ┌──────────────────┐
    │ Get next URI     │
    │ from queue       │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │ Download URI     │
    │ (step 5)         │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │ Discover URIs    │
    │ (step 6)         │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │ Queue discovered │
    │ URIs (step 7)    │
    └────────┬─────────┘
             │
             └──→ Loop back to start
```

---

### 5. Download Phase

**Entry point:** `Downloader::download(DiscoveredUri $uri)`

#### 5.1 Check Download Limit
```
Downloader::download($uri)
├─→ Check if downloadLimit exceeded
│   └─→ If yes, return false
└─→ Continue...
```

#### 5.2 Fetch Resource
```
Downloader::fetchResource($uri)
├─→ Dispatch SPIDER_CRAWL_PRE_REQUEST event
│   └─→ Listeners can:
│       - Log the request
│       - Add delays (politeness policy)
│       - Modify headers (authentication)
│
├─→ RequestHandler::request($uri)
│   └─→ GuzzleRequestHandler::request()
│       ├─→ Guzzle HTTP client GET request
│       ├─→ PSR-7 Response returned
│       └─→ Wrap in Resource object
│
├─→ Dispatch SPIDER_CRAWL_POST_REQUEST event
│   └─→ Always fires (even on error)
│
└─→ On exception:
    ├─→ Dispatch SPIDER_CRAWL_ERROR_REQUEST event
    │   └─→ Event contains: URI, error message
    └─→ Return false
```

#### 5.3 Apply Postfetch Filters
```
Downloader::download($uri)
└─→ matchesPostfetchFilter($resource)
    ├─→ For each PostFetchFilter:
    │   ├─→ Call filter::match($resource)
    │   └─→ If matches (returns true):
    │       ├─→ Dispatch SPIDER_CRAWL_FILTER_POSTFETCH event
    │       └─→ Return true (exclude resource)
    └─→ If any filter matches:
        └─→ Return false (don't persist)
```

#### 5.4 Persist Resource
```
Downloader::download($uri)
└─→ PersistenceHandler::persist($resource)
    └─→ Handler-specific logic:
        - MemoryPersistenceHandler: Add to array
        - FileSerializedResourcePersistenceHandler: Write to file
        - Custom: Database insert, etc.
```

#### 5.5 Dispatch Persisted Event
```
Spider::doCrawl()
└─→ Dispatch SPIDER_CRAWL_RESOURCE_PERSISTED event
    └─→ Event contains: spider instance, URI
```

---

### 6. Discovery Phase

**Entry point:** `DiscovererSet::discover(Resource $resource)`

#### 6.1 Mark URI as Seen
```
DiscovererSet::discover($resource)
└─→ markSeen($resource->getUri())
    └─→ Add URI to $alreadySeenUris map
        └─→ Key: normalized URI string
        └─→ Value: depth found
```

#### 6.2 Check Max Depth
```
DiscovererSet::discover($resource)
└─→ isAtMaxDepth($resource->getUri())
    ├─→ If URI depth == maxDepth:
    │   └─→ Return [] (no discovery)
    └─→ Otherwise, continue...
```

#### 6.3 Run All Discoverers
```
DiscovererSet::discover($resource)
└─→ For each Discoverer:
    ├─→ Discoverer::discover($resource)
    │   └─→ Examples:
    │       - XPathExpressionDiscoverer: XPath query
    │       - CssSelectorDiscoverer: CSS selector query
    │       - Custom: JSON parsing, sitemap, etc.
    │
    └─→ Merge results from all discoverers
```

**Example: XPathExpressionDiscoverer**
```
XPathExpressionDiscoverer::discover($resource)
├─→ Get Symfony Crawler from resource
├─→ Apply XPath expression (e.g., "//a[@href]")
├─→ For each matched node:
│   ├─→ Extract href attribute
│   ├─→ Resolve relative URLs
│   ├─→ Create DiscoveredUri at depth + 1
│   └─→ Add to results
└─→ Return DiscoveredUri[]
```

#### 6.4 Normalize URIs
```
DiscovererSet::discover($resource)
└─→ normalize($discoveredUris)
    └─→ For each URI:
        └─→ DiscoveredUri::normalize()
            ├─→ Remove default ports (80, 443)
            ├─→ Normalize path (/../, /./)
            ├─→ Sort query parameters (if applicable)
            └─→ Lowercase hostname
```

#### 6.5 Remove Duplicates (Within Discovery Batch)
```
DiscovererSet::discover($resource)
└─→ removeDuplicates($discoveredUris)
    ├─→ Convert URIs to strings
    ├─→ Use array_unique()
    └─→ Remove duplicates by array key
```

#### 6.6 Filter Already Seen
```
DiscovererSet::discover($resource)
└─→ filterAlreadySeen($discoveredUris)
    └─→ For each URI:
        ├─→ Check if normalized string exists in $alreadySeenUris
        └─→ If yes, remove from results
```

#### 6.7 Apply Prefetch Filters
```
DiscovererSet::discover($resource)
└─→ filter($discoveredUris)
    └─→ For each URI:
        └─→ For each PreFetchFilter:
            ├─→ Call filter::match($uri)
            └─→ If matches (returns true):
                ├─→ Remove URI from results
                └─→ (No event dispatched for prefetch)
```

**Example filters at this stage:**
- AllowedHostsFilter: Check if host allowed
- AllowedSchemeFilter: Check if scheme is http/https
- UriWithHashFragmentFilter: Check for #fragment
- RobotsTxtDisallowFilter: Check robots.txt rules
- Custom filters

#### 6.8 Mark Discovered URIs as Seen
```
DiscovererSet::discover($resource)
└─→ For each remaining URI:
    └─→ markSeen($uri)
```

#### 6.9 Return Filtered URIs
```
DiscovererSet::discover($resource)
└─→ Return DiscoveredUri[]
```

---

### 7. Queueing Phase

**Entry point:** `QueueManager::addUri(DiscoveredUri $uri)`

For each discovered URI:

```
Spider::doCrawl()
└─→ For each discovered URI:
    ├─→ Try to add to queue
    │   ├─→ QueueManager::addUri($uri)
    │   │   ├─→ Check maxQueueSize
    │   │   │   └─→ If exceeded, throw MaxQueueSizeExceededException
    │   │   ├─→ Increment currentQueueSize
    │   │   ├─→ Add to traversal queue
    │   │   │   └─→ Algorithm-dependent:
    │   │   │       - Depth-first: array_push (LIFO)
    │   │   │       - Breadth-first: array_unshift (FIFO)
    │   │   └─→ Dispatch SPIDER_CRAWL_POST_ENQUEUE event
    │   │
    │   └─→ On MaxQueueSizeExceededException:
    │       └─→ Break loop (stop queueing)
    │
    └─→ Continue to next URI
```

---

### 8. Queue Retrieval

**Entry point:** `QueueManager::next()`

At the start of each crawl loop iteration:

```
Spider::doCrawl()
└─→ while ($currentUri = QueueManager::next())
    └─→ InMemoryQueueManager::next()
        ├─→ If traversalAlgorithm == DEPTH_FIRST:
        │   └─→ array_pop($traversalQueue)  // Last in, first out
        │
        ├─→ If traversalAlgorithm == BREADTH_FIRST:
        │   └─→ array_shift($traversalQueue)  // First in, first out
        │
        └─→ Return DiscoveredUri or null
```

**Traversal Algorithm Comparison:**

```
Given links: A → [B, C], B → [D, E], C → [F]

Depth-First Order:
A → C → F → B → E → D

Breadth-First Order:
A → B → C → D → E → F
```

---

## Termination Phase

### 9. Loop Exit Conditions

The crawl loop exits when:

```
1. Queue is empty
   └─→ QueueManager::next() returns null

2. Download limit reached
   └─→ Downloader::isDownLoadLimitExceeded() returns true
       └─→ PersistenceHandler::count() >= downloadLimit

3. Max queue size exceeded during queueing
   └─→ MaxQueueSizeExceededException thrown
       └─→ Caught in Spider::doCrawl()
       └─→ Breaks inner loop, outer loop continues until queue empty

4. Signal received (CLI only)
   └─→ SIGTERM, SIGINT, SIGHUP, or SIGQUIT
   └─→ Spider::handleSignal() called
   └─→ Dispatches SPIDER_CRAWL_USER_STOPPED event
   └─→ Script can exit in event handler
```

### 10. Post-Crawl

After `Spider::crawl()` returns, users can:

```php
// Access crawled resources
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    echo $resource->getUri()->toString() . "\n";
    echo $resource->getCrawler()->filter('title')->text() . "\n";
}

// Get statistics (if tracking via event listener)
echo "Total crawled: " . $spider->getDownloader()->getPersistenceHandler()->count();
```

---

## Event Timeline

Here's a complete event timeline for a typical crawl:

```
Time  Event                              Component       Description
─────────────────────────────────────────────────────────────────────────
T0    SPIDER_CRAWL_PRE_CRAWL            Spider          Crawl starting
T1    SPIDER_CRAWL_POST_ENQUEUE         QueueManager    Seed queued
      
T2    SPIDER_CRAWL_PRE_REQUEST          Downloader      Before fetching seed
T3    SPIDER_CRAWL_POST_REQUEST         Downloader      After fetching seed
T4    SPIDER_CRAWL_RESOURCE_PERSISTED   Spider          Seed persisted
T5    SPIDER_CRAWL_POST_ENQUEUE         QueueManager    Link 1 queued
T6    SPIDER_CRAWL_POST_ENQUEUE         QueueManager    Link 2 queued
...   ...                               ...             ...

TN    SPIDER_CRAWL_PRE_REQUEST          Downloader      Before fetching link
TN+1  SPIDER_CRAWL_POST_REQUEST         Downloader      After fetching link
TN+2  SPIDER_CRAWL_RESOURCE_PERSISTED   Spider          Link persisted
TN+3  SPIDER_CRAWL_POST_ENQUEUE         QueueManager    Nested link queued
...   ...                               ...             ...

(On error)
TX    SPIDER_CRAWL_PRE_REQUEST          Downloader      Before request
TY    SPIDER_CRAWL_ERROR_REQUEST        Downloader      Request failed
TZ    SPIDER_CRAWL_POST_REQUEST         Downloader      After request (always)

(On postfetch filter match)
TA    SPIDER_CRAWL_FILTER_POSTFETCH     Downloader      Resource filtered out

(On signal)
TS    SPIDER_CRAWL_USER_STOPPED         Spider          User interrupted
```

---

## State Diagrams

### URI Lifecycle

```
┌─────────────┐
│ Discovered  │  Created by Discoverer
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Normalized  │  URI normalized
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Deduplicated│  Checked against already seen
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Prefetch    │  Filters applied
│ Filtered    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Queued      │  Added to QueueManager
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Fetched     │  HTTP request made
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Postfetch   │  Content filters applied
│ Filtered    │
└──────┬──────┘
       │
       ▼
┌─────────────┐
│ Persisted   │  Saved by PersistenceHandler
└─────────────┘
```

### Resource Lifecycle

```
┌──────────────┐
│ HTTP Request │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ PSR-7        │
│ Response     │
└──────┬───────┘
       │
       ▼
┌──────────────┐
│ Wrapped in   │
│ Resource     │
└──────┬───────┘
       │
       ├─→ Postfetch Filtering
       │
       ├─→ Persistence
       │
       └─→ Discovery (lazy Crawler creation)
           │
           └─→ New URIs discovered
```

---

## Detailed Component State

### DiscovererSet State

```
┌──────────────────────────────────┐
│ DiscovererSet                     │
├──────────────────────────────────┤
│ discoverers: []                   │  Empty initially
│ filters: []                       │  Empty initially
│ maxDepth: 3                       │  Default
│ alreadySeenUris: []               │  Grows during crawl
└──────────────────────────────────┘

After configuration:
┌──────────────────────────────────┐
│ discoverers: [XPathDiscoverer]   │  User adds
│ filters: [AllowedHostsFilter]    │  User adds
│ maxDepth: 5                       │  User sets
│ alreadySeenUris: [                │  Spider populates
│   "http://example.com" => 0,     │
│   "http://example.com/page" => 1 │
│ ]                                 │
└──────────────────────────────────┘
```

### QueueManager State

```
Initial:
┌──────────────────────────────────┐
│ InMemoryQueueManager              │
├──────────────────────────────────┤
│ maxQueueSize: 0 (unlimited)       │
│ currentQueueSize: 0               │
│ traversalQueue: []                │
│ traversalAlgorithm: DEPTH_FIRST   │
└──────────────────────────────────┘

After seed queued:
┌──────────────────────────────────┐
│ currentQueueSize: 1               │
│ traversalQueue: [seed]            │
└──────────────────────────────────┘

After discovering 2 links (depth-first):
┌──────────────────────────────────┐
│ currentQueueSize: 3               │
│ traversalQueue: [seed, link1, link2] │
│                       ↑
│                    next() returns link2 (LIFO)
└──────────────────────────────────┘

After discovering 2 links (breadth-first):
┌──────────────────────────────────┐
│ currentQueueSize: 3               │
│ traversalQueue: [link2, link1, seed] │
│                  ↑
│                next() returns link2 (FIFO)
└──────────────────────────────────┘
```

### Downloader State

```
┌──────────────────────────────────┐
│ Downloader                        │
├──────────────────────────────────┤
│ downloadLimit: 0 (unlimited)      │
│ persistenceHandler: Memory        │
│   └─ resources: []                │  Grows during crawl
│   └─ count: 0                     │
│ requestHandler: GuzzleRequestHandler │
│ postFetchFilters: []              │
└──────────────────────────────────┘

After downloading 3 resources:
┌──────────────────────────────────┐
│ persistenceHandler: Memory        │
│   └─ resources: [R1, R2, R3]     │
│   └─ count: 3                     │
└──────────────────────────────────┘
```

---

## Performance Characteristics

### Time Complexity

| Operation | Complexity | Notes |
|-----------|------------|-------|
| Queue URI | O(1) | Array push/unshift |
| Get next URI | O(1) | Array pop/shift |
| Check seen | O(1) | Hashtable lookup |
| Mark seen | O(1) | Hashtable insert |
| Discover (XPath) | O(n) | n = nodes in document |
| Persist (Memory) | O(1) | Array append |
| Persist (File) | O(m) | m = resource size |

### Memory Usage

| Component | Memory Growth |
|-----------|---------------|
| QueueManager | O(maxQueueSize) |
| DiscovererSet.alreadySeenUris | O(total URIs discovered) |
| MemoryPersistenceHandler | O(downloadLimit × avg resource size) |
| FilePersistenceHandler | O(1) - writes to disk |

**Recommendations:**
- For crawls > 10,000 pages: Use `FileSerializedResourcePersistenceHandler`
- For crawls > 100,000 pages: Consider custom queue/seen storage (Redis, database)
- Set `maxQueueSize` and `downloadLimit` to prevent runaway crawls

---

## Debugging Tips

### Enable Event Logging

```php
foreach ([
    SpiderEvents::SPIDER_CRAWL_PRE_CRAWL,
    SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE,
    SpiderEvents::SPIDER_CRAWL_PRE_REQUEST,
    SpiderEvents::SPIDER_CRAWL_POST_REQUEST,
    SpiderEvents::SPIDER_CRAWL_ERROR_REQUEST,
    SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED,
] as $eventName) {
    $spider->getDispatcher()->addListener($eventName, function($event) use ($eventName) {
        echo "[" . date('H:i:s') . "] $eventName\n";
    });
}
```

### Track URI Flow

```php
$spider->getQueueManager()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_POST_ENQUEUE,
    function($event) {
        $uri = $event->getArgument('uri');
        echo "Queued: " . $uri->toString() . " (depth " . $uri->getDepthFound() . ")\n";
    }
);

$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_RESOURCE_PERSISTED,
    function($event) {
        $uri = $event->getArgument('uri');
        echo "Persisted: " . $uri->toString() . "\n";
    }
);
```

### Monitor Filter Activity

```php
// Count filtered URIs
$filteredCount = 0;
$spider->getDownloader()->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_FILTER_POSTFETCH,
    function() use (&$filteredCount) {
        $filteredCount++;
    }
);

// After crawl
echo "Postfetch filtered: $filteredCount\n";
```

---

## Summary

The PHP-Spider lifecycle follows this flow:

1. **Initialize**: Create Spider with seed, configure components
2. **Queue Seed**: Add starting URI to queue
3. **Loop**: While queue not empty and limits not exceeded:
   - Fetch next URI from queue
   - Download resource (with events and error handling)
   - Apply postfetch filters
   - Persist if not filtered
   - Discover URIs from resource
   - Apply prefetch filters
   - Queue discovered URIs (with events)
4. **Terminate**: Exit loop when conditions met
5. **Process**: Access crawled resources from persistence handler

The event system provides hooks at each stage, and the filter system allows fine-grained control over what gets crawled and persisted.

For more information:
- [Architecture Documentation](architecture.md)
- [Extending PHP-Spider](extending.md)
- [Example Scripts](../example/)

# Examples Guide

This guide provides an overview of all examples included with PHP-Spider. Each example demonstrates specific features and use cases to help you understand how to use the library effectively.

## Quick Start Examples

### [example_simple.php](../example/example_simple.php)

**Purpose**: Basic crawling example showing the minimal setup needed to start crawling a website.

**Key Concepts**:
- Creating a Spider instance
- Adding a URI discoverer (XPath-based)
- Setting crawl depth and queue size limits
- Using event listeners for statistics collection
- Processing downloaded resources
- Handling user interrupts (SIGTERM/SIGINT)

**Best For**: First-time users learning the basics of PHP-Spider.

**Complexity**: ⭐ Beginner

---

### [example_basic_auth.php](../example/example_basic_auth.php)

**Purpose**: Demonstrates how to crawl websites that require HTTP Basic Authentication.

**Key Concepts**:
- Configuring custom Guzzle client with authentication
- Using GuzzleRequestHandler with auth credentials
- HTTP Basic Authentication (can be adapted for Digest or NTLM)

**Best For**: Crawling protected websites or APIs requiring authentication.

**Complexity**: ⭐ Beginner

---

## Intermediate Examples

### [example_cache.php](../example/example_cache.php)

**Purpose**: Shows how to use the caching system to avoid re-downloading resources that are already cached.

**Key Concepts**:
- Using `CachedResourceFilter` to skip cached resources
- Setting a fixed spider ID to share cache across runs
- Configuring cache max age (freshness)
- File-based persistence for caching
- Incremental crawling (resume from previous runs)

**Best For**: Long-running crawls, incremental updates, or reducing server load.

**Complexity**: ⭐⭐ Intermediate

**See Also**: [CachedResourceFilter documentation](filters/CachedResourceFilter.md)

---

### [example_persistent_request_params.php](../example/example_persistent_request_params.php)

**Purpose**: Demonstrates how to add persistent request parameters to all HTTP requests.

**Key Concepts**:
- Custom Guzzle client configuration
- Adding query parameters to all requests
- Using Guzzle's request options

**Best For**: API crawling with authentication tokens or tracking parameters.

**Complexity**: ⭐⭐ Intermediate

---

## Advanced Examples

### [example_complex.php](../example/example_complex.php)

**Purpose**: Comprehensive example showing most features of PHP-Spider working together.

**Key Concepts**:
- Custom queue manager with breadth-first traversal
- Multiple prefetch filters (scheme, hosts, hash fragments, query strings, robots.txt)
- File-based persistence handler
- Politeness policy (delays between requests)
- Event listeners and subscribers (stats, logging)
- Guzzle middleware (timing, caching)
- Performance metrics collection
- Advanced XPath expressions for URI discovery

**Best For**: Production crawling with proper etiquette and comprehensive logging.

**Complexity**: ⭐⭐⭐ Advanced

---

### [example_link_check.php](../example/example_link_check.php)

**Purpose**: Demonstrates how to use PHP-Spider as a link checker to find broken links on a website.

**Key Concepts**:
- Custom request handler that doesn't throw exceptions on 4XX/5XX responses
- LinkCheckRequestHandler (allows crawl to continue on errors)
- Capturing and reporting HTTP status codes
- Persisting failed requests for analysis

**Best For**: Website health monitoring, finding broken links, validating external references.

**Complexity**: ⭐⭐⭐ Advanced

**Important Note**: By default, the spider stops on 4XX/5XX errors. This example shows how to override that behavior.

---

### [example_health_check.php](../example/example_health_check.php)

**Purpose**: Specialized health checking that produces lightweight JSON reports of page status.

**Key Concepts**:
- Custom request handler for error tolerance (LinkCheckRequestHandler)
- JsonHealthCheckPersistenceHandler (lightweight JSON output)
- Health status summary and statistics
- Automated monitoring use cases
- CI/CD integration patterns

**Best For**: Continuous monitoring, automated health checks, integration with dashboards.

**Complexity**: ⭐⭐⭐ Advanced

**Output Format**: JSON file with URI, status code, reason phrase, timestamp, and depth for each crawled page.

---

## Supporting Files

### [example_complex_bootstrap.php](../example/example_complex_bootstrap.php)

**Purpose**: Bootstrap file for complex examples that sets up autoloading and common components.

**Contents**:
- Composer autoloader setup
- Custom autoloader for example classes
- Timer middleware initialization
- Start time tracking

**Used By**: example_simple.php, example_complex.php, example_link_check.php, example_health_check.php, example_cache.php

---

## Helper Classes

The `example/lib/Example/` directory contains helper classes used across multiple examples:

### [StatsHandler.php](../example/lib/Example/StatsHandler.php)

**Purpose**: Event subscriber that collects statistics about the crawl.

**Tracks**:
- Enqueued URIs
- Persisted resources
- Filtered (skipped) URIs
- Failed requests with error messages

**Events**: Subscribes to `SPIDER_CRAWL_POST_ENQUEUE`, `SPIDER_CRAWL_RESOURCE_PERSISTED`, `SPIDER_CRAWL_FILTER_*`, `SPIDER_CRAWL_ERROR_REQUEST`

---

### [LogHandler.php](../example/lib/Example/LogHandler.php)

**Purpose**: Event subscriber that logs crawl events to console.

**Features**:
- Debug mode for verbose logging
- Logs queued, persisted, filtered, and failed URIs

**Events**: Subscribes to same events as StatsHandler

---

### [LinkCheckRequestHandler.php](../example/lib/Example/LinkCheckRequestHandler.php)

**Purpose**: Custom request handler that doesn't throw exceptions on HTTP errors.

**Key Difference**: Sets `http_errors => false` in Guzzle options, allowing the spider to continue crawling even when encountering 4XX/5XX responses.

**Used By**: example_link_check.php, example_health_check.php

---

### [GuzzleTimerMiddleware.php](../example/lib/Example/GuzzleTimerMiddleware.php)

**Purpose**: Guzzle middleware that tracks request timing.

**Provides**: Total request time for performance metrics.

**Used By**: example_complex.php, example_link_check.php, example_health_check.php

---

## Running the Examples

All examples are self-contained PHP scripts that can be run from the command line:

```bash
cd example
php example_simple.php
php example_complex.php
# ... etc
```

### Prerequisites

Make sure you have installed dependencies:

```bash
composer install
```

### Stopping Examples

All examples that perform actual crawling support graceful shutdown via signals:
- Press `Ctrl+C` to stop the crawl
- The spider will emit a `SPIDER_CRAWL_USER_STOPPED` event
- Statistics will be displayed before exit

---

## Example Comparison Matrix

| Example | Complexity | Authentication | Caching | Error Handling | Persistence | Best Use Case |
|---------|-----------|---------------|---------|----------------|-------------|---------------|
| example_simple.php | ⭐ | No | No | Default (stops on errors) | Memory | Learning basics |
| example_basic_auth.php | ⭐ | Yes (Basic) | No | Default | Memory | Protected sites |
| example_cache.php | ⭐⭐ | No | Yes | Default | File | Incremental crawls |
| example_persistent_request_params.php | ⭐⭐ | Custom params | No | Default | Memory | API crawling |
| example_complex.php | ⭐⭐⭐ | No | No | Default | File | Production crawling |
| example_link_check.php | ⭐⭐⭐ | No | No | Custom (continues on errors) | File | Link checking |
| example_health_check.php | ⭐⭐⭐ | No | No | Custom (continues on errors) | JSON | Health monitoring |

---

## Common Patterns

### Pattern 1: Basic Crawl Setup

```php
use VDB\Spider\Spider;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;

$spider = new Spider('https://example.com');
$spider->getDiscovererSet()->addDiscoverer(new XPathExpressionDiscoverer('//a'));
$spider->getDiscovererSet()->setMaxDepth(2);
$spider->crawl();
```

### Pattern 2: Adding Filters

```php
use VDB\Spider\Filter\Prefetch\AllowedSchemeFilter;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;

$spider->getDiscovererSet()->addFilter(new AllowedSchemeFilter(['http', 'https']));
$spider->getDiscovererSet()->addFilter(new AllowedHostsFilter(['example.com'], true));
```

### Pattern 3: Event Handling

```php
use VDB\Spider\Event\SpiderEvents;

$spider->getDispatcher()->addListener(
    SpiderEvents::SPIDER_CRAWL_POST_REQUEST,
    function ($event) {
        echo "Downloaded: " . $event->getArgument('uri')->toString() . "\n";
    }
);
```

### Pattern 4: Custom Persistence

```php
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;

$spider->getDownloader()->setPersistenceHandler(
    new FileSerializedResourcePersistenceHandler('/path/to/results')
);
```

---

## Further Reading

- [Architecture Documentation](architecture.md) - Learn about the spider's internal structure
- [Lifecycle Documentation](lifecycle.md) - Understand the crawl process flow
- [Extending PHP-Spider](extending.md) - Create custom components
- [Filters Documentation](filters/) - Available filters and how to use them
- [Main README](../README.md) - Installation and quick start guide

---

## Need Help?

- **Report Issues**: [GitHub Issues](https://github.com/mvdbos/php-spider/issues)
- **Ask Questions**: Open an issue with the "question" label
- **Contribute**: Pull requests are welcome! See [CONTRIBUTING](../README.md#contributing)

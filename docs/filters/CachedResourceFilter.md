# CachedResourceFilter

The `CachedResourceFilter` is a prefetch filter that allows you to skip downloading resources that are already cached and younger than a specified maximum age. This is useful for incremental crawls where you want to avoid re-downloading recently fetched content.

## Features

- Skip downloading resources that are already cached on disk
- Configurable maximum age for cached resources
- Works with file-based persistence handlers
- Share cache across multiple spider runs by using the same spider ID

## Usage

### Basic Example

```php
use VDB\Spider\Spider;
use VDB\Spider\Filter\Prefetch\CachedResourceFilter;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;

// Use a fixed spider ID to share cache across runs
$spiderId = 'my-spider-cache';
$spider = new Spider('http://example.com', null, null, null, $spiderId);

// Set up file persistence
$resultsPath = __DIR__ . '/cache';
$spider->getDownloader()->setPersistenceHandler(
    new FileSerializedResourcePersistenceHandler($resultsPath)
);

// Add the cache filter with a 1-hour max age
$maxAgeSeconds = 3600; // 1 hour
$cacheFilter = new CachedResourceFilter($resultsPath, $spiderId, $maxAgeSeconds);
$spider->getDiscovererSet()->addFilter($cacheFilter);

// Crawl - cached resources within 1 hour will be skipped
$spider->crawl();
```

### Configuration Options

#### Constructor Parameters

- `$basePath` (string): The base directory where spider results are stored
- `$spiderId` (string): The spider ID used for the cache directory (must match the persistence handler's spider ID)
- `$maxAgeSeconds` (int): Maximum age in seconds for cached resources. Set to `0` to always use cache regardless of age.

### Max Age Behavior

The `$maxAgeSeconds` parameter controls how the filter determines if a cached resource is "fresh":

- **`$maxAgeSeconds > 0`**: Only skip downloading if the cached file's modification time is less than `$maxAgeSeconds` seconds old
- **`$maxAgeSeconds = 0`**: Always skip downloading if the file exists in cache, regardless of age (useful for archival crawls)

### Examples

#### Always use cache (archival mode)

```php
// Never re-download cached resources
$cacheFilter = new CachedResourceFilter($resultsPath, $spiderId, 0);
$spider->getDiscovererSet()->addFilter($cacheFilter);
```

#### Daily incremental crawls

```php
// Re-download resources older than 24 hours
$maxAgeSeconds = 86400; // 24 hours
$cacheFilter = new CachedResourceFilter($resultsPath, $spiderId, $maxAgeSeconds);
$spider->getDiscovererSet()->addFilter($cacheFilter);
```

#### Hourly updates

```php
// Re-download resources older than 1 hour
$maxAgeSeconds = 3600; // 1 hour
$cacheFilter = new CachedResourceFilter($resultsPath, $spiderId, $maxAgeSeconds);
$spider->getDiscovererSet()->addFilter($cacheFilter);
```

## Important Notes

### Spider ID Consistency

For the cache to work across multiple runs, you **must** use the same spider ID:

```php
// ✓ Correct: Fixed spider ID for cache sharing
$spiderId = 'my-consistent-id';
$spider = new Spider($seed, null, null, null, $spiderId);
$cacheFilter = new CachedResourceFilter($resultsPath, $spiderId, $maxAge);

// ✗ Wrong: Auto-generated spider ID (different each run)
$spider = new Spider($seed); // Spider ID is random each time
$cacheFilter = new CachedResourceFilter($resultsPath, 'some-other-id', $maxAge); // Won't find cache from previous run
```

### Persistence Handler Compatibility

The cache filter is designed to work with file-based persistence handlers:

- `FileSerializedResourcePersistenceHandler` ✓
- `FilePersistenceHandler` ✓
- `MemoryPersistenceHandler` ✗ (stores in memory, not disk)

### Cache Directory Structure

The cache filter expects the same directory structure as the file persistence handlers:

```
{basePath}/{spiderId}/{hostname}/{path}/{encoded-filename}
```

For example:
```
cache/my-spider-id/example.com/path/to/page.html
```

## How It Works

1. When a URI is discovered, the cache filter checks if a corresponding file exists in the cache directory
2. If the file exists, it checks the file's modification time
3. If the file is younger than `$maxAgeSeconds` (or `$maxAgeSeconds` is 0), the filter returns `true` to skip downloading
4. If the file doesn't exist or is too old, the filter returns `false` to allow downloading

## Complete Example

See `example/example_cache.php` for a complete working example.

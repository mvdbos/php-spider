# PHP-Spider – Copilot Guide

- Project shape: a configurable crawler around Guzzle + Symfony components (dom-crawler, css-selector, finder, event-dispatcher) and vdb/uri. Entry point and orchestration live in [src/Spider.php](src/Spider.php); examples are in [example/](example/).
- Crawl loop: `Spider::crawl()` seeds the queue, sets the persistence handler spider id, fires a `spider.crawl.pre_crawl` event, then iterates `doCrawl()` pulling URIs from the queue, downloading, persisting, dispatching `spider.crawl.resource.persisted`, and feeding discoveries back into the queue.
- Traversal and queueing: [src/QueueManager/InMemoryQueueManager.php](src/QueueManager/InMemoryQueueManager.php) defaults to depth-first; switch with `setTraversalAlgorithm(ALGORITHM_BREADTH_FIRST)`. `maxQueueSize` stops discovery once reached (throws `MaxQueueSizeExceededException`); enqueue emits `spider.crawl.post.enqueue`.
- Discovery pipeline: [src/Discoverer/DiscovererSet.php](src/Discoverer/DiscovererSet.php) holds discoverers + prefetch filters, tracks already-seen URIs, and enforces `maxDepth` (default 3) to stop recursion. Register discoverers via `addDiscoverer()` and filters via `addFilter()`.
- Download pipeline: [src/Downloader/Downloader.php](src/Downloader/Downloader.php) uses a `RequestHandlerInterface` (default [GuzzleRequestHandler](src/RequestHandler/GuzzleRequestHandler.php)) and a `PersistenceHandlerInterface` (default [MemoryPersistenceHandler](src/PersistenceHandler/MemoryPersistenceHandler.php)). `downloadLimit` caps persisted resources. Postfetch filters run before persistence and emit `spider.crawl.filter.postfetch`.
- Resource model: [src/Resource.php](src/Resource.php) wraps `DiscoveredUri` + PSR-7 response and lazily creates a Symfony `Crawler` with response body and content-type; it serializes by storing the raw message for file-based persistence.
- Persistence options: in-memory for small runs; file-based handlers in [src/PersistenceHandler](src/PersistenceHandler) write per-spider-id directories and serialize resources (`FileSerializedResourcePersistenceHandler` keeps the PSR-7 response intact). Set `setSpiderId()` before persisting.
- URI model: [src/Uri/DiscoveredUri.php](src/Uri/DiscoveredUri.php) decorates `vdb/uri` with `depthFound` to drive depth filtering and normalization/de-duplication.
- Filters: prefetch filters live in [src/Filter/Prefetch](src/Filter/Prefetch) (e.g., `RestrictToBaseUriFilter`, `AllowedHostsFilter`, regex-based `UriFilter`, robots.txt-aware `RobotsTxtDisallowFilter`); postfetch filters in [src/Filter/Postfetch](src/Filter/Postfetch) (e.g., `MimeTypeFilter`). Filters return true to skip.
- Events and extensibility: events declared in [src/Event/SpiderEvents.php](src/Event/SpiderEvents.php); dispatcher shared via [DispatcherTrait](src/Event/DispatcherTrait.php). Typical listeners: pre-request throttling ([src/EventListener/PolitenessPolicyListener.php](src/EventListener/PolitenessPolicyListener.php) hooks `spider.crawl.pre_request`) and stats collection example in [example/lib/Example/StatsHandler.php](example/lib/Example/StatsHandler.php).
- HTTP handling: default Guzzle handler throws on 4XX/5XX; to keep crawling on errors, supply a custom `RequestHandlerInterface` (see link-checker example referenced in [README](README.md)). Signals (SIGTERM/SIGINT/etc.) trigger `spider.crawl.user.stopped` when running in CLI.
- Key tuning knobs: `DiscovererSet::$maxDepth`, `QueueManager::$maxQueueSize`, `Downloader::setDownloadLimit()`, traversal algorithm, request delay via politeness listener, robots.txt user-agent.
- Coding standards: PSR-0/1/2; codebase targets PHP >= 8.0. Autoload via PSR-4 `VDB\Spider\` from `src/`.

## Development & Testing Workflow

### Fast Iteration During Development
- **For quick feedback:** Use `./vendor/bin/phpunit [test-file]` to run specific tests
- **Optional fast checks:** Run `php -l` on changed files (no dependencies, instant syntax validation)
- **Example:** `./vendor/bin/phpunit tests/Discoverer/DiscovererSetTest.php`
- **Example:** `find src/ -name "*.php" | xargs -n1 php -l`

### Mandatory Validation Before Commits and PRs
- **ALWAYS run `./bin/check` before EVERY commit and before creating/updating ANY pull request**
- This is the **single source of truth** for validation
- Runs the complete CI workflow: lint, phpcs (PSR2), phpmd, phan, and phpunit with 100% coverage
- Uses `./bin/act --matrix php-versions:8.0` to run GitHub Actions locally with the lowest supported PHP version
- **DO NOT** commit without running `./bin/check` first
- **DO NOT** run individual static analysis tools (phpcs, phpmd, phan) manually - `./bin/check` runs them all correctly

### Validation Commands Reference
```bash
# FAST: During development iterations (multiple times)
./vendor/bin/phpunit                    # All tests, no coverage
./vendor/bin/phpunit tests/SomeTest.php # Specific test file
php -l src/SomeFile.php                 # Syntax check (optional, no deps)

# MANDATORY: Before EVERY commit and PR
./bin/check                             # Full CI validation (required before commit/PR)

# Equivalents (same as ./bin/check)
./bin/act --matrix php-versions:8.0     # Explicit form of ./bin/check
```

## Testing & Static Analysis
- Static analysis: The full CI workflow includes lint, phpcs (PSR2), phpmd (with [phpmd.xml](phpmd.xml) and [phpmd-tests.xml](phpmd-tests.xml)), phan, and coverage enforcement. All checks are run via `./bin/check`.
- Common workflow: `composer install`, make code changes, run `./vendor/bin/phpunit` for quick feedback on specific tests, then **ALWAYS run `./bin/check` before committing (MANDATORY before every commit and PR)**.
- When adding features: wire new events through `DispatcherTrait`, keep discovery depth/visited tracking in sync (normalize URIs), and ensure new persistence handlers implement Iterator + Countable to align with Downloader expectations.
- Testing patterns: examples drive expected behaviors (queue, filters, stats). Add unit tests under [tests/](tests) to keep coverage at 100% and satisfy CI scripts.
- **Code Coverage Requirements**: This project **requires 100% line coverage** for all code. This is non-negotiable and enforced by CI:
  - **ALWAYS** ensure your code changes maintain 100% coverage before submitting.
  - If you encounter pre-existing coverage gaps (code that was not at 100% before your changes), you **MUST** fix those gaps as well, even if you didn't introduce them.
  - Coverage is validated by `./bin/check` - no need to run `./bin/coverage-enforce` manually
  - Use `XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-html build/coverage` to generate HTML reports for identifying uncovered lines.
  - **Never** submit code that reduces overall project coverage below 100%, regardless of whether the uncovered code is yours or pre-existing.
  - Write comprehensive tests that cover all code paths: normal cases, edge cases, error conditions, and boundary conditions.

## Commit and PR Requirements (MANDATORY)

**CRITICAL: Before EVERY commit and before creating/finalizing ANY pull request, you MUST:**

1. **Run the full validation suite:**
   ```bash
   ./bin/check
   ```

2. **Verify ALL checks pass:**
   - Linting (PSR-1/2 compliance)
   - phpcs, phpmd, phan static analysis
   - phpunit with 100% code coverage
   - All tests on PHP 8.0

3. **If any check fails:**
   - Fix all issues
   - Re-run `./bin/check`
   - Repeat until all checks are green

4. **Only after full validation passes** may you:
   - Commit your changes
   - Create or update the pull request

**DO NOT commit or create/finalize a PR with failing validations.** This is non-negotiable.

### Why This Workflow?

- **Fast iteration**: `./vendor/bin/phpunit` gives instant feedback during coding
- **Complete validation**: `./bin/check` ensures all CI checks pass before commit/PR
- **Prevents CI failures**: Running `./bin/check` locally catches issues before pushing
- **Single source of truth**: `./bin/check` runs the exact same checks as GitHub Actions CI
- **No surprises**: If `./bin/check` passes, CI will pass

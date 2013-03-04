[![Build Status](https://travis-ci.org/matthijsvandenbos/php-spider.png?branch=master)](https://travis-ci.org/matthijsvandenbos/php-spider)

README
======
A configurable and extensible PHP web spider

Usage
-----

This is a very simple example. This code can be found in `example/example_simple.php`. For a more complete example with
some logging, caching and filters, see `example/example_complex.php`. That file contains a more real-world example.

First create the spider
```php
use VDB\Spider\Spider;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;

$spider = new Spider('http://www.dmoz.org');
```
Add a URI discoverer. Without it, the spider does nothing. In this case, we want all `<a>` nodes from a certain `<div>`

```php
$spider->addDiscoverer(new XPathExpressionDiscoverer("//div[@id='catalogs']//a"));
```
Set some sane options for this example. In this case, we only get the first 10 items from the start page.
```php
$spider->setMaxDepth(1);
$spider->setMaxQueueSize(10);
```
Execute crawl
```php
$report = $spider->crawl();
```
And finally, we could get some info about the crawl
```php
echo "\nENQUEUED: " . count($report['queued']);
echo "\n - ".implode("\n - ", $report['queued']);
echo "\nSKIPPED:   " . count($report['filtered']);
echo "\nFAILED:    " . count($report['failed']) . "\n";
```

TODO
----
### MUST HAVE

- [ ] refactor: Make the processqueue an injectable interface with some default adapters for file, memcache. etc.
- [ ] refactor: make the returned report an injectable interface with some default adapters for file, memcache. Also contains info about where to find the process queue
- [ ] refactor: make the spider accept an array of seeds
- [ ] build: support robots.txt.
- [ ] build: ranking policy listener. This can listen to the SPIDER_CRAWL_POST_DISCOVER event. We need to refactor $discoveredLinks to an object to let the listener change it. Question: Do we want to do that without copying the array because of memory usage?
- [ ] build: a re-visit policy that states when to check for changes to the pages (
- [ ] check: when calculating maxdepth, are redirects counted?

### SHOULD HAVE

- [ ] design: Support RDF, RSS Atom, Twitter feeds
- [ ] decide: maybe make it possible to set a minimum depth for a discoverer.  Use case: index pages where detail pages have other markup structure
- [ ] build: support scaling: multiple parallel threads / workers. For requests? or for spiders themselves? or both?
- [ ] build: support authentication
- [ ] build: Phar compilation support





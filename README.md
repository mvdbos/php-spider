[![Build Status](https://travis-ci.org/matthijsvandenbos/php-spider.png?branch=master)](https://travis-ci.org/matthijsvandenbos/php-spider)

PHP-Spider Features
======
- supports two traversal algorithms: breadth-first and depth-first
- supports depth limiting and queue size limiting
- supports adding custom URI discovery logic, based on XPath, CSS selectors, or plain old PHP
- comes with a useful set of prebuilt URI filters, such as Domain limiting
- supports custom filters, both prefetch (URI) and postfetch (Resource content)
- supports custom request handling logic
- dispatches useful events, allowing developers to add even more custom behavior
- supports a politeness policy
- will soon come with many default discoverers: RSS, Atom, RDF, etc.
- will soon support multiple queueing and persistence mechanisms (file, memcache, redis)
- will eventually support distributed spidering with a central queue

Usage
-----
This is a very simple example. This code can be found in [example/example_simple.php](https://github.com/matthijsvandenbos/php-spider/blob/master/example/example_simple.php). For a more complete example with
some logging, caching and filters, see [example/example_complex.php](https://github.com/matthijsvandenbos/php-spider/blob/master/example/example_complex.php). That file contains a more real-world example.

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
When crawling is done, we could get some info about the crawl
```php
echo "\nENQUEUED: " . count($report['queued']);
echo "\n - ".implode("\n - ", $report['queued']);
echo "\nSKIPPED:   " . count($report['filtered']);
echo "\nFAILED:    " . count($report['failed']) . "\n";
```
Finally we could start some processing on the downloaded resources
```php
foreach ($report['queued'] as $resource) {
    $title = $resource->getCrawler()->filterXpath('//title')->text();
    $contentLength = $resource->getResponse()->getHeader('Content-Length');
    // do something with the data
    echo "\n - ".  str_pad("[" . round($contentLength / 1024), 4, ' ', STR_PAD_LEFT) . "KB] $title";
}
```
Contributing
------------
Contributing to PHP-Spider is as easy as Forking the repository on Github and submitting a Pull Request. 
The Symfony documentation contains an excellent guide for how to do that properly here: [Submitting a Patch](http://symfony.com/doc/current/contributing/code/patches.html#step-1-setup-your-environment).

There a few requirements for a Pull Request to be accepted:
- Follow the coding standards: PHP-Spider follows the coding standards defined in the [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md), [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) Coding Style Guides;
- Prove that the code works with unit tests;

> Note: An easy way to check if your code conforms to PHP-Spider is by running [Scrutinizer](https://scrutinizer-ci.com/) on your local code. You can do it simply by downloading [scrutinizer.phar](https://scrutinizer-ci.com/scrutinizer.phar) and running it on your PHP-Spider repository like so: `php scrutinizer.phar run /path/to/php-spider`

License
-------
PHP-Spider is licensed under the MIT license.

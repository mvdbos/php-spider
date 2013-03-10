[![Build Status](https://travis-ci.org/matthijsvandenbos/php-spider.png?branch=master)](https://travis-ci.org/matthijsvandenbos/php-spider)

PHP-Spider Features
======
- supports two traversal algorithms: breadth-first and depth-first
- supports depth limiting and queue size limiting
- supports adding custom URI discovery logic, based on XPath, CSS selectors, or plain old PHP
- comes with a useful set of URI filters, such as Domain limiting
- supports custom URI filters, both prefetch (URI) and postfetch (Resource content)
- supports custom request handling logic
- comes with a useful set of persistence handlers (memory, file. Redis soon to follow)
- supports custom persistence handlers
- collects statistics about the crawl for reporting
- dispatches useful events, allowing developers to add even more custom behavior
- supports a politeness policy
- will soon come with many default discoverers: RSS, Atom, RDF, etc.
- will soon support multiple queueing mechanisms (file, memcache, redis)
- will eventually support distributed spidering with a central queue

Installation
------------
The easiest way to install PHP-Spider is with [composer](http://getcomposer.org/).  Find it on [Packagist](https://packagist.org/packages/vdb/php-spider).
> Note: if you want to run the examples or unit tests, you need to do `composer install --dev`, so that all dependencies for the examples also get installed.

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
Execute the crawl
```php
$spider->crawl();
```
When crawling is done, we could get some info about the crawl
```php
$stats = $spider->getStatsHandler();
echo "\nSPIDER ID: " . $stats->getSpiderId();
echo "\n  ENQUEUED:  " . count($stats->getQueued());
echo "\n  SKIPPED:   " . count($stats->getFiltered());
echo "\n  FAILED:    " . count($stats->getFailed());
```
Finally we could do some processing on the downloaded resources. In this example, we will echo the title of all resources
```php
echo "\n\nDOWNLOADED RESOURCES: ";
foreach ($spider->getPersistenceHandler() as $resource) {
    echo "\n - " . $resource->getCrawler()->filterXpath('//title')->text();
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

Support
-------
For things like reporting bugs and requesting features it is best to create an [issue](https://github.com/matthijsvandenbos/php-spider/issues) here on GitHub. It is even better to accompany it with a Pull Request. ;-)

If you have other questions, or need some tips, you can send a tweet to [@phpspider](https://twitter.com/phpspider) or send an email to support@php-spider.org.

License
-------
PHP-Spider is licensed under the MIT license.

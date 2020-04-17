![Build Status](https://github.com/mvdbos/php-spider/workflows/PHP-Spider/badge.svg?branch=master)
[![Latest Stable Version](https://poser.pugx.org/vdb/php-spider/v/stable)](https://packagist.org/packages/vdb/php-spider)
[![Total Downloads](https://poser.pugx.org/vdb/php-spider/downloads)](https://packagist.org/packages/vdb/php-spider)
[![License](https://poser.pugx.org/vdb/php-spider/license)](https://packagist.org/packages/vdb/php-spider)

> Note on backwards compatibility break: since v0.5.0, Symfony EventDispatcher v3 is no longer supported and PHP Spider requires v4 or v5. If you are stuck with v3, you can still use PHP Spider v0.4.x. The reason for this is because of a BC break in the EventDispatcher v5, which we needed to support to keep up with modern frameworks.

PHP-Spider Features
======
- supports two traversal algorithms: breadth-first and depth-first
- supports crawl depth limiting, queue size limiting and max downloads limiting
- supports adding custom URI discovery logic, based on XPath, CSS selectors, or plain old PHP
- comes with a useful set of URI filters, such as Domain limiting
- supports custom URI filters, both prefetch (URI) and postfetch (Resource content)
- supports custom request handling logic
- comes with a useful set of persistence handlers (memory, file)
- supports custom persistence handlers
- collects statistics about the crawl for reporting
- dispatches useful events, allowing developers to add even more custom behavior
- supports a politeness policy

This Spider does not support Javascript.

Installation
------------
The easiest way to install PHP-Spider is with [composer](https://getcomposer.org/).  Find it on [Packagist](https://packagist.org/packages/vdb/php-spider).

Usage
-----
This is a very simple example. This code can be found in [example/example_simple.php](https://github.com/matthijsvandenbos/php-spider/blob/master/example/example_simple.php). For a more complete example with some logging, caching and filters, see [example/example_complex.php](https://github.com/matthijsvandenbos/php-spider/blob/master/example/example_complex.php). That file contains a more real-world example.

First create the spider
```php
$spider = new Spider('http://www.dmoz.org');
```
Add a URI discoverer. Without it, the spider does nothing. In this case, we want all `<a>` nodes from a certain `<div>`

```php
$spider->getDiscovererSet()->set(new XPathExpressionDiscoverer("//div[@id='catalogs']//a"));
```
Set some sane options for this example. In this case, we only get the first 10 items from the start page.

```php
$spider->getDiscovererSet()->maxDepth = 1;
$spider->getQueueManager()->maxQueueSize = 10;
```
Add a listener to collect stats from the Spider and the QueueManager.
There are more components that dispatch events you can use.

```php
$statsHandler = new StatsHandler();
$spider->getQueueManager()->getDispatcher()->addSubscriber($statsHandler);
$spider->getDispatcher()->addSubscriber($statsHandler);
```
Execute the crawl

```php
$spider->crawl();
```
When crawling is done, we could get some info about the crawl
```php
echo "\n  ENQUEUED:  " . count($statsHandler->getQueued());
echo "\n  SKIPPED:   " . count($statsHandler->getFiltered());
echo "\n  FAILED:    " . count($statsHandler->getFailed());
echo "\n  PERSISTED:    " . count($statsHandler->getPersisted());
```
Finally we could do some processing on the downloaded resources. In this example, we will echo the title of all resources
```php
echo "\n\nDOWNLOADED RESOURCES: ";
foreach ($spider->getDownloader()->getPersistenceHandler() as $resource) {
    echo "\n - " . $resource->getCrawler()->filterXpath('//title')->text();
}

```
Contributing
------------
Contributing to PHP-Spider is as easy as Forking the repository on Github and submitting a Pull Request.
The Symfony documentation contains an excellent guide for how to do that properly here: [Submitting a Patch](http://symfony.com/doc/current/contributing/code/patches.html#step-1-setup-your-environment).

There a few requirements for a Pull Request to be accepted:
- Follow the coding standards: PHP-Spider follows the coding standards defined in the [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md), [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) Coding Style Guides;
- Prove that the code works with unit tests and that coverage remains above 60%;

> Note: An easy way to check if your code conforms to PHP-Spider is by running the script `bin/static-analysis`, which is part of this repo. This will run the following tools, configured for PHP-Spider: PHP CodeSniffer, PHP Mess Detector and PHP Copy/Paste Detector.  

> Note: To run PHPUnit with coverage, and to check that coverage >= 60%, you can run `bin/coverage-enforce 60`.

Support
-------
For things like reporting bugs and requesting features it is best to create an [issue](https://github.com/mvdbos/php-spider/issues) here on GitHub. It is even better to accompany it with a Pull Request. ;-)

License
-------
PHP-Spider is licensed under the MIT license.

<?php
/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\EventListener;

use VDB\Spider\Tests\TestCase;
use VDB\Spider\EventListener\PolitenessPolicyListener;
use VDB\Spider\Event\SpiderEvents;
use Symfony\Component\EventDispatcher\GenericEvent;
use VDB\Uri\Uri;

/**
 *
 */
class PolitenessPolicyListenerTest extends TestCase
{
    /**
     * @covers VDB\Spider\EventListener\PolitenessPolicyListener
     */
    public function testOnCrawlPreRequestSameDomain()
    {
        $politenessPolicyListener = new PolitenessPolicyListener(500);

        $uri = new Uri('http://php-spider.org/', 'http://php-spider.org/');
        $event = new GenericEvent(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, array('uri' => $uri));

        $politenessPolicyListener->onCrawlPreRequest($event);

        $start = microtime(true);
        $politenessPolicyListener->onCrawlPreRequest($event);
        $interval = microtime(true) - $start;

        $this->assertGreaterThanOrEqual(0.5, $interval, 'Actual delay');
    }

    /**
     * @covers VDB\Spider\EventListener\PolitenessPolicyListener
     */
    public function testOnCrawlPreRequestDifferentDomain()
    {
        $politenessPolicyListener = new PolitenessPolicyListener(500);

        $uri = new Uri('http://php-spider.org/', 'http://php-spider.org/');
        $event = new GenericEvent(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, array('uri' => $uri));

        $uri2 = new Uri('http://example.com/', 'http://example.com/');
        $event2 = new GenericEvent(SpiderEvents::SPIDER_CRAWL_PRE_REQUEST, array('uri' => $uri2));

        $politenessPolicyListener->onCrawlPreRequest($event);

        $start = microtime(true);
        $politenessPolicyListener->onCrawlPreRequest($event2);
        $interval = microtime(true) - $start;

        $this->assertLessThan(0.5, $interval, 'Actual delay');
    }
}

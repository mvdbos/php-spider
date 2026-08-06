<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Discoverer;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversMethod;
use Exception;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedPortsFilter;
use VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter;
use VDB\Spider\Filter\Prefetch\UriFilter;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversClass(\VDB\Spider\Discoverer\DiscovererSet::class)]
#[CoversClass(\VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter::class)]
#[CoversClass(\VDB\Spider\Filter\Prefetch\AllowedPortsFilter::class)]
#[CoversClass(\VDB\Spider\Filter\Prefetch\AllowedHostsFilter::class)]
#[CoversMethod(\VDB\Spider\Discoverer\DiscovererSet::class, 'getMaxDepth')]
#[CoversMethod(\VDB\Spider\Discoverer\DiscovererSet::class, 'setMaxDepth')]
#[CoversMethod(\VDB\Spider\Discoverer\DiscovererSet::class, 'addDiscoverer')]
#[CoversMethod(\VDB\Spider\Discoverer\DiscovererSet::class, 'set')]
#[CoversMethod(\VDB\Spider\Discoverer\DiscovererSet::class, 'addFilter')]
class DiscovererSetTest extends DiscovererTestCase
{
    private DiscovererSet $discovererSet;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testMaxDepth()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);
        $this->discovererSet->setMaxDepth(1);

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);

        $this->discovererSet->setMaxDepth(0);
        $urisAtDepth0 = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(0, $urisAtDepth0);
    }

    public function testConstructor()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    public function testAddDiscoverer()
    {
        $this->discovererSet = new DiscovererSet();
        $this->discovererSet->addDiscoverer(new XPathExpressionDiscoverer("//a"));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    public function testSetDiscovererBackwardCompatibility()
    {
        $this->discovererSet = new DiscovererSet();
        
        // Suppress deprecation warning for this backward compatibility test
        set_error_handler(function ($errno, $errstr) {
            if ($errno === E_USER_DEPRECATED) {
                return true; // Suppress the deprecation warning
            }
            return false; // Let other errors through
        });
        
        $this->discovererSet->set(new XPathExpressionDiscoverer("//a"));
        
        restore_error_handler();

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    public function testUriFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new UriFilter(['/^.*contact.*$/']));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
    }

    public function testRobotsTxtDisallowFilter()
    {
        $baseUri = "file://" . __DIR__;
        $resourceUri = new DiscoveredUri($baseUri, 0);
        $uriInBody1 = $baseUri . '/internal';
        $uriInBody2 = $baseUri . '/foo';

        $spiderResource = self::createResourceWithLinks($resourceUri, [$uriInBody1, $uriInBody2]);

        $discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);
        $discovererSet->addFilter(new RobotsTxtDisallowFilter($baseUri));

        $uris = $discovererSet->discover($spiderResource);
        $this->assertCount(1, $uris);
        $this->assertNotContains($uriInBody2, array_map(fn($uri): string => $uri->toString(), $uris));
    }

    public function testPortFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new AllowedPortsFilter([8080]));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
        $this->assertEquals($this->uriInBody2, $uris[0]->toString());
    }

    public function testHostFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new AllowedHostsFilter(array("http://php-spider.org")));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    public function testInvalidUriSkipped()
    {
        $resourceUri = new DiscoveredUri('http://php-spider.org/', 0);
        $uriInBody1 = 'http://php-spider:org:8080:internal/';
        $uriInBody2 = 'http://php-spider.org:8080/internal/';

        // Setup DOM
        $spiderResource = self::createResourceWithLinks($resourceUri, [$uriInBody1, $uriInBody2]);

        $discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $uris = $discovererSet->discover($spiderResource);
        $this->assertCount(1, $uris);
    }

    public function testDuplicatesRemoved()
    {

        // Setup DOM
        $spiderResource = self::createResourceWithLinks(
            new DiscoveredUri('http://php-spider.org/', 0),
            ['http://php-spider.org:8080/internal/', 'http://php-spider.org:8080/internal/']
        );

        $discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $uris = $discovererSet->discover($spiderResource);
        $this->assertCount(1, $uris);
    }

    public function testAlreadySeenSkipped()
    {
        $resourceUri = new DiscoveredUri('http://php-spider.org:8080/internal/', 0);

        // Setup DOM
        $spiderResource = self::createResourceWithLinks(
            $resourceUri,
            ['http://php-spider.org:8080/internal/']
        );

        $discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $uris = $discovererSet->discover($spiderResource);
        $this->assertCount(0, $uris);
    }

    public function testGetSetMaxDepth()
    {
        $discovererSet = new DiscovererSet();

        // Test default value
        $this->assertEquals(3, $discovererSet->getMaxDepth());

        // Test setter and getter
        $result = $discovererSet->setMaxDepth(5);
        $this->assertEquals(5, $discovererSet->getMaxDepth());

        // Test method chaining
        $this->assertSame($discovererSet, $result);

        // Test with zero
        $discovererSet->setMaxDepth(0);
        $this->assertEquals(0, $discovererSet->getMaxDepth());
    }

    public function testAddDiscovererReturnsThis()
    {
        $discovererSet = new DiscovererSet();
        $discoverer = new XPathExpressionDiscoverer("//a");

        $result = $discovererSet->addDiscoverer($discoverer);

        // Test method chaining
        $this->assertSame($discovererSet, $result);
    }

    public function testSetReturnsThisBackwardCompatibility()
    {
        $discovererSet = new DiscovererSet();
        $discoverer = new XPathExpressionDiscoverer("//a");

        // Suppress deprecation warning for this backward compatibility test
        set_error_handler(function ($errno, $errstr) {
            if ($errno === E_USER_DEPRECATED) {
                return true; // Suppress the deprecation warning
            }
            return false; // Let other errors through
        });
        
        $result = $discovererSet->set($discoverer);
        
        restore_error_handler();

        // Test method chaining for backward compatibility
        $this->assertSame($discovererSet, $result);
    }

    public function testAddFilterReturnsThis()
    {
        $discovererSet = new DiscovererSet();
        $filter = new AllowedHostsFilter(['http://example.com']);

        $result = $discovererSet->addFilter($filter);

        // Test method chaining
        $this->assertSame($discovererSet, $result);
    }

    public function testMethodChaining()
    {
        $discovererSet = new DiscovererSet();

        // Test fluent interface with method chaining
        $result = $discovererSet
            ->setMaxDepth(2)
            ->addDiscoverer(new XPathExpressionDiscoverer("//a"))
            ->addFilter(new AllowedHostsFilter(['http://example.com']));

        $this->assertSame($discovererSet, $result);
        $this->assertEquals(2, $discovererSet->getMaxDepth());
    }
}

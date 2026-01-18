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

use ErrorException;
use Exception;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedPortsFilter;
use VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter;
use VDB\Spider\Filter\Prefetch\UriFilter;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\FileUri;

/**
 *
 */
class DiscovererSetTest extends DiscovererTestCase
{
    private DiscovererSet $discovererSet;

    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     */
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


    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     */
    public function testConstructor()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     */
    public function testAddDiscoverer()
    {
        $this->discovererSet = new DiscovererSet();
        $this->discovererSet->addDiscoverer(new XPathExpressionDiscoverer("//a"));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     */
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

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     */
    public function testUriFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new UriFilter(['/^.*contact.*$/']));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     * @covers \VDB\Spider\Filter\Prefetch\RobotsTxtDisallowFilter
     *
     * @throws UriSyntaxException
     * @throws ErrorException
     * @throws Exception
     */
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
        $this->assertNotContains((new FileUri($uriInBody2))->toString(), array_map(fn($uri): string => (new FileUri($uri->toString()))->toString(), $uris));
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     * @covers \VDB\Spider\Filter\Prefetch\AllowedPortsFilter
     */
    public function testPortFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new AllowedPortsFilter([8080]));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
        $this->assertEquals($this->uriInBody2, $uris[0]->toString());
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     * @covers \VDB\Spider\Filter\Prefetch\AllowedHostsFilter
     */
    public function testHostFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new AllowedHostsFilter(array("http://php-spider.org")));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     * @throws Exception
     */
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

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     * @throws Exception
     */
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

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet
     */
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

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet::getMaxDepth
     * @covers \VDB\Spider\Discoverer\DiscovererSet::setMaxDepth
     */
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

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet::addDiscoverer
     */
    public function testAddDiscovererReturnsThis()
    {
        $discovererSet = new DiscovererSet();
        $discoverer = new XPathExpressionDiscoverer("//a");

        $result = $discovererSet->addDiscoverer($discoverer);

        // Test method chaining
        $this->assertSame($discovererSet, $result);
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet::set
     */
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

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet::addFilter
     */
    public function testAddFilterReturnsThis()
    {
        $discovererSet = new DiscovererSet();
        $filter = new AllowedHostsFilter(['http://example.com']);

        $result = $discovererSet->addFilter($filter);

        // Test method chaining
        $this->assertSame($discovererSet, $result);
    }

    /**
     * @covers \VDB\Spider\Discoverer\DiscovererSet::addDiscoverer
     * @covers \VDB\Spider\Discoverer\DiscovererSet::addFilter
     * @covers \VDB\Spider\Discoverer\DiscovererSet::setMaxDepth
     */
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

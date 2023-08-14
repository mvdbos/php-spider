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
use VDB\Spider\Filter\Prefetch\UriFilter;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

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
        $this->discovererSet->maxDepth = 1;

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);

        $this->discovererSet->maxDepth = 0;
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
    public function testSetDiscoverer()
    {
        $this->discovererSet = new DiscovererSet();
        $this->discovererSet->set(new XPathExpressionDiscoverer("//a"));

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
     * @throws UriSyntaxException
     * @throws ErrorException
     * @throws Exception
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
}

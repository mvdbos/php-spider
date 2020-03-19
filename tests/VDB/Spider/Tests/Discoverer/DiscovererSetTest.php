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

use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\AllowedPortsFilter;
use VDB\Spider\Filter\Prefetch\UriFilter;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class DiscovererSetTest extends DiscovererTestCase
{
    /**
     * @var DiscovererSet
     */
    private $discovererSet;


    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     */
    public function testConstructor()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     */
    public function testSetDiscoverer()
    {
        $this->discovererSet = new DiscovererSet();
        $this->discovererSet->set(new XPathExpressionDiscoverer("//a"));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     */
    public function testUriFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new UriFilter(['/^.*contact.*$/']));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     * @covers VDB\Spider\Filter\Prefetch\AllowedPortsFilter
     */
    public function testPortFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new AllowedPortsFilter([8080]));

        /** @var DiscoveredUri[] $uris */
        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
        $this->assertEquals($this->uriInBody2, $uris[0]->toString());
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     * @covers VDB\Spider\Filter\Prefetch\AllowedHostsFilter
     */
    public function testHostFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new AllowedHostsFilter(array("http://php-spider.org")));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(2, $uris);
    }
}

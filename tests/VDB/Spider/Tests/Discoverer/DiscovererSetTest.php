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

use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Discoverer\DiscovererSet;
use VDB\Spider\Filter\Prefetch\UriFilter;

/**
 *
 */
class DiscovererSetTest extends DiscovererTestCase
{
    /**
     * @var DiscovererSet
     */
    private $discovererSet;


    public function setUp()
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
        $this->assertCount(1, $uris);
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     */
    public function testSetDiscoverer()
    {
        $this->discovererSet = new DiscovererSet();
        $this->discovererSet->set(new XPathExpressionDiscoverer("//a"));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(1, $uris);
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     */
    public function testFilter()
    {
        $this->discovererSet = new DiscovererSet([new XPathExpressionDiscoverer("//a")]);

        $this->discovererSet->addFilter(new UriFilter(['/^.*contact.*$/']));

        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(0, $uris);
    }
}

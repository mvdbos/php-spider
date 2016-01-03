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
    private $discovererSet;


    public function setUp()
    {
        parent::setUp();

        $this->discovererSet = new DiscovererSet();
        $this->discovererSet->set(new XPathExpressionDiscoverer("//a"));
        $this->discovererSet->addFilter(new UriFilter(['/^.*contact.*$/']));
    }

    /**
     * @covers VDB\Spider\Discoverer\DiscovererSet
     * @covers VDB\Spider\Filter\Prefetch\UriFilter::match()
     */
    public function testFilter()
    {
        $uris = $this->discovererSet->discover($this->spiderResource);
        $this->assertCount(0, $uris);
    }
}

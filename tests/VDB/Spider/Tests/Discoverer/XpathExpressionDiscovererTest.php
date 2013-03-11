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

/**
 *
 */
class XpathExpressionDiscovererTest extends DiscovererTestCase
{
    /**
     * @covers VDB\Spider\Discoverer\XPathExpressionDiscoverer::discover()
     */
    public function testDiscover()
    {
        $discoverer = new XPathExpressionDiscoverer("//a");
        $uris = $discoverer->discover($this->spider, $this->spiderResource);
        $uri = $uris[0];

        $this->assertInstanceOf('VDB\\URI\\Uri', $uri);
        $this->assertEquals($this->uri->toString(), $uri->toString());
    }
}

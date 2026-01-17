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

use Exception;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use VDB\Spider\Discoverer\SimpleXPathExpressionDiscoverer;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class SimpleXPathExpressionDiscovererTest extends DiscovererTestCase
{
    /**
     * @covers \VDB\Spider\Discoverer\SimpleXPathExpressionDiscoverer
     */
    public function testDiscover()
    {
        $discoverer = new SimpleXPathExpressionDiscoverer("//a");
        $this->executeDiscoverer($discoverer);
    }

    /**
     * @covers \VDB\Spider\Discoverer\SimpleXPathExpressionDiscoverer
     * @throws Exception
     */
    public function testDiscoverWithPath()
    {
        // Create test with div structure
        $uri = new DiscoveredUri('http://php-spider.org/', 0);
        $uriInBody = 'http://php-spider.org/contact/';
        
        $html = '<html><div id="content"><a href="' . $uriInBody . '">Link</a></div></html>';
        $resource = new Resource($uri, new Response(200, [], $html));
        
        $discoverer = new SimpleXPathExpressionDiscoverer("//div[@id='content']//a");
        $uris = $discoverer->discover($resource);
        
        $this->assertCount(1, $uris);
        $this->assertEquals($uriInBody, $uris[0]->toString());
    }

    public function testDiscoverNoA()
    {
        $this->expectException(InvalidArgumentException::class);

        $discoverer = new SimpleXPathExpressionDiscoverer("//b");
        $this->executeDiscoverer($discoverer);
    }

    public function testDiscoverWithBrackets()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Please end your selector with '/a'");

        // SimpleXPathExpressionDiscoverer does not support bracket notation
        $discoverer = new SimpleXPathExpressionDiscoverer("//a[starts-with(@href, '/')]");
        $this->executeDiscoverer($discoverer);
    }
}

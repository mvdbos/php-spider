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
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 *
 */
class XpathExpressionDiscovererTest extends DiscovererTestCase
{
    /**
     * @covers \VDB\Spider\Discoverer\XPathExpressionDiscoverer<extended>
     */
    public function testDiscover()
    {
        $discoverer = new XPathExpressionDiscoverer("//a");
        $this->executeDiscoverer($discoverer);
    }

    /**
     * @covers \VDB\Spider\Discoverer\XPathExpressionDiscoverer<extended>
     */
    public function testDiscoverWithBrackets()
    {
        // Test that bracket notation is now supported
        $discoverer = new XPathExpressionDiscoverer("//a[starts-with(@href, 'http')]");
        $this->executeDiscoverer($discoverer);
    }

    /**
     * @covers \VDB\Spider\Discoverer\XPathExpressionDiscoverer<extended>
     */
    public function testDiscoverWithOrPredicate()
    {
        // Test XPath with or condition
        $discoverer = new XPathExpressionDiscoverer("//a[starts-with(@href, '/') or starts-with(@href, 'http')]");
        $this->executeDiscoverer($discoverer);
    }

    /**
     * @covers \VDB\Spider\Discoverer\XPathExpressionDiscoverer<extended>
     * @throws Exception
     */
    public function testDiscoverWithPath()
    {
        // Create test with div structure
        $uri = new DiscoveredUri('http://php-spider.org/', 0);
        $uriInBody = 'http://php-spider.org/contact/';
        
        $html = '<html><div id="content"><a href="' . $uriInBody . '">Link</a></div></html>';
        $resource = new Resource($uri, new Response(200, [], $html));
        
        $discoverer = new XPathExpressionDiscoverer("//div[@id='content']//a");
        $uris = $discoverer->discover($resource);
        
        $this->assertCount(1, $uris);
        $this->assertEquals($uriInBody, $uris[0]->toString());
    }

    /**
     * @covers \VDB\Spider\Discoverer\XPathExpressionDiscoverer<extended>
     * @throws Exception
     */
    public function testDiscoverWithMultiplePredicates()
    {
        // Create test with complex structure
        $uri = new DiscoveredUri('http://php-spider.org/', 0);
        $uriInBody = 'http://php-spider.org/internal/';
        
        $html = '<html><div id="content"><a href="' . $uriInBody . '" class="link">Link</a></div></html>';
        $resource = new Resource($uri, new Response(200, [], $html));
        
        // Test complex XPath with multiple predicates
        $discoverer = new XPathExpressionDiscoverer("//div[@id='content']//a[@class='link']");
        $uris = $discoverer->discover($resource);
        
        $this->assertCount(1, $uris);
        $this->assertEquals($uriInBody, $uris[0]->toString());
    }

    public function testDiscoverNoA()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must target anchor ('a') elements");

        $discoverer = new XPathExpressionDiscoverer("//b");
        $this->executeDiscoverer($discoverer);
    }

    public function testDiscoverWithDivOnly()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("must target anchor ('a') elements");

        $discoverer = new XPathExpressionDiscoverer("//div[@id='content']");
        $this->executeDiscoverer($discoverer);
    }
}

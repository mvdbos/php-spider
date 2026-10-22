<?php

namespace VDB\Spider\Tests\Helpers;

use PHPUnit\Framework\Attributes\CoversClass;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Tests\TestCase;

/**
 * Tests for the SpiderTestBuilder test helper
 */
#[CoversClass(\VDB\Spider\Tests\Helpers\SpiderTestBuilder::class)]
class SpiderTestBuilderTest extends TestCase
{
    public function testBasicSpiderCreation()
    {
        $spider = SpiderTestBuilder::create()
            ->build();
        
        $this->assertInstanceOf('VDB\Spider\Spider', $spider);
    }

    public function testWithSeed()
    {
        $spider = SpiderTestBuilder::create()
            ->withSeed('http://test.com')
            ->build();
        
        $this->assertInstanceOf('VDB\Spider\Spider', $spider);
    }

    public function testWithLinkMap()
    {
        $spider = SpiderTestBuilder::create()
            ->withSeed('http://example.com')
            ->withLinkMap([
                'http://example.com' => '<a href="http://example.com/page1">Link 1</a>',
                'http://example.com/page1' => '<a href="http://example.com/page2">Link 2</a>',
            ])
            ->build();
        
        // Verify the spider was built
        $this->assertInstanceOf('VDB\Spider\Spider', $spider);
        
        // Verify the request handler was created
        $requestHandler = $spider->getDownloader()->getRequestHandler();
        $this->assertInstanceOf('VDB\Spider\RequestHandler\RequestHandlerInterface', $requestHandler);
    }

    public function testWithSpiderId()
    {
        $spider = SpiderTestBuilder::create()
            ->withSpiderId('test-spider-id')
            ->build();
        
        $this->assertEquals('test-spider-id', $spider->getSpiderId());
    }

    public function testFluentInterface()
    {
        $spider = SpiderTestBuilder::create()
            ->withSeed('http://example.com')
            ->withSpiderId('test-id')
            ->withLinkMap([
                'http://example.com' => '<html><body>Test</body></html>',
            ])
            ->build();
        
        $this->assertEquals('test-id', $spider->getSpiderId());
    }
}

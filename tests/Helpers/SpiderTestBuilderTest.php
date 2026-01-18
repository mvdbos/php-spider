<?php

namespace VDB\Spider\Tests\Helpers;

use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Tests\TestCase;

/**
 * Tests for the SpiderTestBuilder test helper
 */
class SpiderTestBuilderTest extends TestCase
{
    /**
     * @covers \VDB\Spider\Tests\Helpers\SpiderTestBuilder
     */
    public function testBasicSpiderCreation()
    {
        $spider = SpiderTestBuilder::create()
            ->build();
        
        $this->assertInstanceOf('VDB\Spider\Spider', $spider);
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\SpiderTestBuilder
     */
    public function testWithSeed()
    {
        $spider = SpiderTestBuilder::create()
            ->withSeed('http://test.com')
            ->build();
        
        $this->assertInstanceOf('VDB\Spider\Spider', $spider);
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\SpiderTestBuilder
     */
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

    /**
     * @covers \VDB\Spider\Tests\Helpers\SpiderTestBuilder
     */
    public function testWithSpiderId()
    {
        $spider = SpiderTestBuilder::create()
            ->withSpiderId('test-spider-id')
            ->build();
        
        $this->assertEquals('test-spider-id', $spider->getSpiderId());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\SpiderTestBuilder
     */
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

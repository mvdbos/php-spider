<?php

namespace VDB\Spider\Tests\Helpers;

use VDB\Spider\Tests\TestCase;

/**
 * Tests for the ResourceBuilder test helper
 */
class ResourceBuilderTest extends TestCase
{
    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testBasicResourceCreation()
    {
        $resource = ResourceBuilder::create()->build();
        
        $this->assertEquals('http://example.com', $resource->getUri()->toString());
        $this->assertEquals(200, $resource->getResponse()->getStatusCode());
        $this->assertStringContainsString('Test', $resource->getResponse()->getBody()->__toString());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithUri()
    {
        $resource = ResourceBuilder::create()
            ->withUri('http://test.com/page')
            ->build();
        
        $this->assertEquals('http://test.com/page', $resource->getUri()->toString());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithDepth()
    {
        $resource = ResourceBuilder::create()
            ->withDepth(5)
            ->build();
        
        $this->assertEquals(5, $resource->getUri()->getDepthFound());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithStatusCode()
    {
        $resource = ResourceBuilder::create()
            ->withStatusCode(404)
            ->build();
        
        $this->assertEquals(404, $resource->getResponse()->getStatusCode());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithHeaders()
    {
        $resource = ResourceBuilder::create()
            ->withHeaders(['Content-Type' => 'text/html', 'X-Custom' => 'value'])
            ->build();
        
        $this->assertEquals('text/html', $resource->getResponse()->getHeaderLine('Content-Type'));
        $this->assertEquals('value', $resource->getResponse()->getHeaderLine('X-Custom'));
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithHeader()
    {
        $resource = ResourceBuilder::create()
            ->withHeader('X-Test', 'test-value')
            ->build();
        
        $this->assertEquals('test-value', $resource->getResponse()->getHeaderLine('X-Test'));
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithBody()
    {
        $body = '<html><body>Custom content</body></html>';
        $resource = ResourceBuilder::create()
            ->withBody($body)
            ->build();
        
        $this->assertEquals($body, $resource->getResponse()->getBody()->__toString());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithHtmlContent()
    {
        $resource = ResourceBuilder::create()
            ->withHtmlContent('Page Title', '<p>Page content</p>')
            ->build();
        
        $body = $resource->getResponse()->getBody()->__toString();
        $this->assertStringContainsString('Page Title', $body);
        $this->assertStringContainsString('<p>Page content</p>', $body);
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithLinks()
    {
        $resource = ResourceBuilder::create()
            ->withLinks([
                '/page1' => 'Link 1',
                '/page2' => 'Link 2',
            ])
            ->build();
        
        $crawler = $resource->getCrawler();
        $links = $crawler->filter('a');
        
        $this->assertCount(2, $links);
        $this->assertEquals('Link 1', $links->eq(0)->text());
        $this->assertEquals('/page1', $links->eq(0)->attr('href'));
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testWithJsonBody()
    {
        $data = ['key' => 'value', 'array' => [1, 2, 3]];
        $resource = ResourceBuilder::create()
            ->withJsonBody($data)
            ->build();
        
        $this->assertEquals('application/json', $resource->getResponse()->getHeaderLine('Content-Type'));
        $this->assertEquals(json_encode($data), $resource->getResponse()->getBody()->__toString());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testBuildUri()
    {
        $uri = ResourceBuilder::create()
            ->withUri('http://test.com/path')
            ->withDepth(3)
            ->buildUri();
        
        $this->assertEquals('http://test.com/path', $uri->toString());
        $this->assertEquals(3, $uri->getDepthFound());
    }

    /**
     * @covers \VDB\Spider\Tests\Helpers\ResourceBuilder
     */
    public function testFluentInterface()
    {
        $resource = ResourceBuilder::create()
            ->withUri('http://example.com/test')
            ->withDepth(2)
            ->withStatusCode(200)
            ->withHeader('Content-Type', 'text/html')
            ->withBody('<html><body>Test</body></html>')
            ->build();
        
        $this->assertEquals('http://example.com/test', $resource->getUri()->toString());
        $this->assertEquals(2, $resource->getUri()->getDepthFound());
        $this->assertEquals(200, $resource->getResponse()->getStatusCode());
        $this->assertEquals('text/html', $resource->getResponse()->getHeaderLine('Content-Type'));
        $this->assertStringContainsString('Test', $resource->getResponse()->getBody()->__toString());
    }
}

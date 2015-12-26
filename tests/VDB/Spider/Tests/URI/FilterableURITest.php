<?php
namespace VDB\Spider\Tests\Uri;

use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\FilterableUri;

/**
 *
 */
class FilterableUriTest extends TestCase
{
    /**
     * @var FilterableUri
     */
    protected $uri;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->uri = new FilterableUri(
            '/domains/special',
            'http://example.org'
        );
    }

    /**
     * @covers VDB\Spider\Uri\FilterableUri::setFiltered
     * @covers VDB\Spider\Uri\FilterableUri::isFiltered
     * @covers VDB\Spider\Uri\FilterableUri::getFilterReason
     */
    public function testSetFiltered()
    {
        $this->uri->setFiltered(true, 'goodReason');
        $this->assertTrue($this->uri->isFiltered());
        $this->assertEquals('goodReason', $this->uri->getFilterReason());
    }

    /**
     * @covers VDB\Spider\Uri\FilterableUri::getIdentifier
     * @todo   Implement testGetIdentifier().
     */
    public function testGetIdentifier()
    {
        $this->assertEquals('http://example.org/domains/special', $this->uri->getIdentifier());
    }
}

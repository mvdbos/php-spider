<?php
namespace VDB\Spider\Tests\URI;

use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\FilterableURI;

/**
 *
 */
class FilterableURITest extends TestCase
{
    /**
     * @var FilterableURI
     */
    protected $uri;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->uri = new FilterableURI(
            '/domains/special',
            'http://example.org'
        );
    }

    /**
     * @covers VDB\Spider\FilterableURI::setFiltered
     * @covers VDB\Spider\FilterableURI::isFiltered
     * @covers VDB\Spider\FilterableURI::getFilterReason
     */
    public function testSetFiltered()
    {
        $this->uri->setFiltered(true, 'goodReason');
        $this->assertTrue($this->uri->isFiltered());
        $this->assertEquals('goodReason', $this->uri->getFilterReason());
    }

    /**
     * @covers VDB\Spider\FilterableURI::getIdentifier
     * @todo   Implement testGetIdentifier().
     */
    public function testGetIdentifier()
    {
        $this->assertEquals('http://example.org/domains/special', $this->uri->getIdentifier());
    }
}

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

use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Filter\Postfetch\MimeTypeFilter;
use VDB\Uri\Uri;

class MimeTypeFilterTest extends TestCase
{
    /** @var Resource */
    protected $spiderResource;

    /** @var DiscoveredUri */
    protected $uri;

    protected function setUp()
    {
        $this->uri = new DiscoveredUri(new Uri('http://foobar.com/image.jpg'));

        $this->spiderResource = new Resource(
            $this->uri,
            new Response(200, ['Content-Type' => 'image/jpeg'], '')
        );
    }

    /**
     * @covers VDB\Spider\Filter\Postfetch\MimeTypeFilter
     */
    public function testMimeTypeFilter()
    {
        $filter = new MimeTypeFilter('text/html');
        $this->assertTrue($filter->match($this->spiderResource));

        $filter = new MimeTypeFilter('image/jpeg');
        $this->assertFalse($filter->match($this->spiderResource));
    }
}

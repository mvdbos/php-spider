<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Filter\Postfetch;

use ErrorException;
use VDB\Spider\Filter\Postfetch\MimeTypeFilter;
use VDB\Spider\Resource;
use VDB\Spider\Tests\Helpers\ResourceBuilder;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Exception\UriSyntaxException;

class MimeTypeFilterTest extends TestCase
{
    protected Resource $spiderResource;

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    protected function setUp(): void
    {
        $this->spiderResource = ResourceBuilder::create()
            ->withUri('http://foobar.com/image.jpg')
            ->withHeader('Content-Type', 'image/jpeg')
            ->build();
    }

    /**
     * @covers \VDB\Spider\Filter\Postfetch\MimeTypeFilter
     */
    public function testMimeTypeFilter()
    {
        $filter = new MimeTypeFilter('text/html');
        $this->assertTrue($filter->match($this->spiderResource));

        $filter = new MimeTypeFilter('image/jpeg');
        $this->assertFalse($filter->match($this->spiderResource));
    }
}

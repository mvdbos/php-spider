<?php
/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Uri;

use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;
use VDB\Spider\Uri\UriDecorator;

/**
 *
 */
class UriDecoratorTest extends TestCase
{
    /**
     * @covers VDB\Spider\Uri\UriDecorator
     */
    public function testConstruct()
    {
        $stub = $this->getMockForAbstractClass('VDB\\Spider\\Uri\\UriDecorator', ['http://example.org']);
        $this->assertEquals('http://example.org', $stub->toString());

        $stub = $this->getMockForAbstractClass('VDB\\Spider\\Uri\\UriDecorator', [new Uri('http://example.org')]);
        $this->assertEquals('http://example.org', $stub->toString());
    }
}

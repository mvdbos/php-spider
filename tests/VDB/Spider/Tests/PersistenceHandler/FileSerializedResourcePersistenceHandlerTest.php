<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Downloader;

use GuzzleHttp\Psr7\Response;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Resource;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Uri\Uri;

/**
 *
 */
class FileSerializedResourcePersistenceHandlerTest extends TestCase
{
    /**
     * @var Resource
     */
    protected $resource;

    /**
     * @var string
     */
    protected $html;

    /**
     * @var FileSerializedResourcePersistenceHandler
     */

    protected $handler;

    public function setUp()
    {
        $this->html = file_get_contents(__DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html');
        $this->resource = new Resource(
            new DiscoveredUri(new Uri('/domains/special/test.html', 'http://example.org')),
            new Response(200, [], $this->html)
        );

        $this->handler = new FileSerializedResourcePersistenceHandler(sys_get_temp_dir());
        $this->handler->setSpiderId('spider-UT');
    }

    /**
     * @covers VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler
     */
    public function testPersist()
    {
        $this->handler->persist($this->resource);

        $expectedFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spider-UT' . DIRECTORY_SEPARATOR .'example.org/domains/special/test.html';
        $this->assertFileExists($expectedFilePath);

        $savedResource = unserialize(file_get_contents($expectedFilePath));
        $this->assertEquals(
            $this->html,
            $savedResource->getResponse()->getBody()
        );
    }
}

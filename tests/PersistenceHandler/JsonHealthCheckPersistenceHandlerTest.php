<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\PersistenceHandler;

use GuzzleHttp\Psr7\Response;
use VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

class JsonHealthCheckPersistenceHandlerTest extends TestCase
{
    /**
     * @var JsonHealthCheckPersistenceHandler
     */
    protected JsonHealthCheckPersistenceHandler $handler;

    /**
     * @var string
     */
    protected string $tmpDir;

    public function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/spider-test-' . uniqid();
        mkdir($this->tmpDir, 0777, true);

        $this->handler = new JsonHealthCheckPersistenceHandler($this->tmpDir);
        $this->handler->setSpiderId('test-spider');
    }

    public function tearDown(): void
    {
        // Clean up test files
        $files = glob($this->tmpDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tmpDir)) {
            rmdir($this->tmpDir);
        }
    }

    /**
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::persist
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::count
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::setSpiderId
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::getJsonFilePath
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::writeToFile
     */
    public function testPersist()
    {
        $resource1 = new Resource(
            new DiscoveredUri("http://example.com/page1", 0),
            new Response(200, [], "Test Body 1")
        );

        $resource2 = new Resource(
            new DiscoveredUri("http://example.com/page2", 1),
            new Response(404, [], "Not Found")
        );

        $resource3 = new Resource(
            new DiscoveredUri("http://example.com/page3", 1),
            new Response(500, [], "Internal Server Error")
        );

        $this->handler->persist($resource1);
        $this->handler->persist($resource2);
        $this->handler->persist($resource3);

        $this->assertEquals(3, $this->handler->count());

        // Check that JSON file was created
        $jsonFile = $this->tmpDir . '/test-spider_health_check.json';
        $this->assertFileExists($jsonFile);

        // Read and validate JSON content
        $jsonContent = file_get_contents($jsonFile);
        $data = json_decode($jsonContent, true);

        $this->assertIsArray($data);
        $this->assertCount(3, $data);

        // Validate first result
        $this->assertEquals('http://example.com/page1', $data[0]['uri']);
        $this->assertEquals(200, $data[0]['status_code']);
        $this->assertEquals('OK', $data[0]['reason_phrase']);
        $this->assertEquals(0, $data[0]['depth']);
        $this->assertArrayHasKey('timestamp', $data[0]);

        // Validate second result
        $this->assertEquals('http://example.com/page2', $data[1]['uri']);
        $this->assertEquals(404, $data[1]['status_code']);
        $this->assertEquals('Not Found', $data[1]['reason_phrase']);
        $this->assertEquals(1, $data[1]['depth']);

        // Validate third result
        $this->assertEquals('http://example.com/page3', $data[2]['uri']);
        $this->assertEquals(500, $data[2]['status_code']);
        $this->assertEquals('Internal Server Error', $data[2]['reason_phrase']);
        $this->assertEquals(1, $data[2]['depth']);
    }

    /**
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::current
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::next
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::key
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::valid
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::rewind
     */
    public function testIterator()
    {
        $resource1 = new Resource(
            new DiscoveredUri("http://example.com/page1", 0),
            new Response(200, [], "Test Body 1")
        );

        $resource2 = new Resource(
            new DiscoveredUri("http://example.com/page2", 1),
            new Response(404, [], "Not Found")
        );

        $this->handler->persist($resource1);
        $this->handler->persist($resource2);

        $results = [];
        foreach ($this->handler as $key => $result) {
            $results[$key] = $result;
        }

        $this->assertCount(2, $results);
        $this->assertEquals('http://example.com/page1', $results[0]['uri']);
        $this->assertEquals(200, $results[0]['status_code']);
        $this->assertEquals('http://example.com/page2', $results[1]['uri']);
        $this->assertEquals(404, $results[1]['status_code']);
    }

    /**
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::setSpiderId
     */
    public function testSetSpiderIdCreatesDirectory()
    {
        $newTmpDir = sys_get_temp_dir() . '/spider-test-new-' . uniqid();

        // Make sure directory doesn't exist
        $this->assertDirectoryDoesNotExist($newTmpDir);

        $handler = new JsonHealthCheckPersistenceHandler($newTmpDir);
        $handler->setSpiderId('new-spider');

        // Directory should be created
        $this->assertDirectoryExists($newTmpDir);

        // Clean up
        rmdir($newTmpDir);
    }

    /**
     * @covers \VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::count
     */
    public function testCountEmpty()
    {
        $this->assertEquals(0, $this->handler->count());
    }
}

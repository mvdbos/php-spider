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

use PHPUnit\Framework\Attributes\CoversMethod;
use GuzzleHttp\Psr7\Response;
use ReflectionClass;
use RuntimeException;
use VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, '__construct')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'getJsonFilePath')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'setSpiderId')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'persist')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'count')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'writeToFile')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'current')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'next')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'key')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'valid')]
#[CoversMethod(\VDB\Spider\PersistenceHandler\JsonHealthCheckPersistenceHandler::class, 'rewind')]
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
        mkdir($this->tmpDir, 0700, true);

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

    public function testConstruct()
    {
        $path = sys_get_temp_dir() . '/spider-construct-test';
        $handler = new JsonHealthCheckPersistenceHandler($path);

        // Using reflection to check the path was set
        $reflection = new ReflectionClass($handler);
        $pathProperty = $reflection->getProperty('path');

        $this->assertEquals($path, $pathProperty->getValue($handler));
    }

    public function testGetJsonFilePath()
    {
        $path = sys_get_temp_dir() . '/spider-path-test';
        $handler = new JsonHealthCheckPersistenceHandler($path);
        $handler->setSpiderId('test-id');

        // Use reflection to call protected method
        $reflection = new ReflectionClass($handler);
        $method = $reflection->getMethod('getJsonFilePath');

        $result = $method->invoke($handler);
        $expected = $path . DIRECTORY_SEPARATOR . 'test-id_health_check.json';
        
        $this->assertEquals($expected, $result);
    }

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

    public function testSetSpiderIdWithExistingDirectory()
    {
        // Test when directory already exists
        $handler = new JsonHealthCheckPersistenceHandler($this->tmpDir);
        $handler->setSpiderId('existing-spider');

        // Should not throw any errors
        $this->assertDirectoryExists($this->tmpDir);
    }

    public function testCountEmpty()
    {
        $this->assertEquals(0, $this->handler->count());
    }

    public function testExistingOutputFileDoesNotContributeToInMemoryCount(): void
    {
        $jsonFile = $this->tmpDir . '/test-spider_health_check.json';
        file_put_contents($jsonFile, '[{"uri":"http://example.com/previous"}]');

        $this->assertSame(0, $this->handler->count());

        $this->handler->persist(
            new Resource(new DiscoveredUri('http://example.com/current', 0), new Response(200, [], 'Current'))
        );

        $this->assertSame(1, $this->handler->count());
        $this->assertCount(1, json_decode(file_get_contents($jsonFile), true));
    }

    public function testWriteToFileHandlesFailure()
    {
        // Skip test if running as root (common in Docker) since root can write to read-only files
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Test cannot run as root user (root can write to read-only files)');
        }

        // Create a directory and make the JSON file read-only to simulate write failure
        $testDir = sys_get_temp_dir() . '/spider-test-readonly-' . uniqid();
        mkdir($testDir, 0700, true);

        $handler = new JsonHealthCheckPersistenceHandler($testDir);
        $handler->setSpiderId('test-readonly');

        // Create the JSON file and make it read-only
        $jsonFile = $testDir . '/test-readonly_health_check.json';
        file_put_contents($jsonFile, '[]');
        chmod($jsonFile, 0444); // Read-only

        $resource = new Resource(
            new DiscoveredUri("http://example.com/test", 0),
            new Response(200, [], "Test Body")
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write JSON file');

        try {
            $handler->persist($resource);
        } finally {
            // Clean up - restore write permissions before deleting
            chmod($jsonFile, 0644);
            unlink($jsonFile);
            rmdir($testDir);
        }
    }

    public function testWriteToFileHandlesFailureWithInvalidDirectory()
    {
        // Test write failure by making the directory read-only after creation
        // This prevents writing the JSON file
        $testDir = sys_get_temp_dir() . '/spider-test-readonly-dir-' . uniqid();
        mkdir($testDir, 0700, true);

        $handler = new JsonHealthCheckPersistenceHandler($testDir);
        $handler->setSpiderId('test-readonly-dir');

        // Make the directory read-only (no write permission)
        chmod($testDir, 0555);

        $resource = new Resource(
            new DiscoveredUri("http://example.com/test", 0),
            new Response(200, [], "Test Body")
        );

        // Skip if running as root (root can write to read-only directories)
        if (posix_getuid() === 0) {
            chmod($testDir, 0755);
            rmdir($testDir);
            $this->markTestSkipped('Test cannot run as root user (root can write to read-only directories)');
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write JSON file');

        try {
            $handler->persist($resource);
        } finally {
            // Clean up - restore write permissions before deleting
            chmod($testDir, 0755);
            rmdir($testDir);
        }
    }

    public function testWriteToFileWithJsonEncodingFailure()
    {
        // Create a handler and use reflection to inject invalid data that will fail JSON encoding
        $handler = new JsonHealthCheckPersistenceHandler(sys_get_temp_dir());
        $handler->setSpiderId('test-json-failure');

        // Use reflection to inject data with invalid UTF-8 that will cause json_encode to fail
        $reflection = new ReflectionClass($handler);
        $resultsProperty = $reflection->getProperty('results');
        
        // Inject invalid UTF-8 data
        $resultsProperty->setValue($handler, [
            ['data' => "\xB1\x31"] // Invalid UTF-8 sequence
        ]);

        // Use reflection to call writeToFile
        $method = $reflection->getMethod('writeToFile');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to encode results to JSON');

        $method->invoke($handler);
    }

    public function testWriteToFileHandlesFailureByMockingFilePutContents()
    {
        // Test by trying to write to a path inside a regular file (not a directory)
        // This will fail for all users including root
        
        $tmpDir = sys_get_temp_dir() . '/spider-file-not-dir-' . uniqid();
        mkdir($tmpDir, 0755, true);
        
        $tmpFile = $tmpDir . '/file-barrier';
        touch($tmpFile);
        
        // Try to use a file path as if it were a directory - this will fail for all users including root
        $handler = new JsonHealthCheckPersistenceHandler($tmpFile);
        $handler->setSpiderId('test');
        
        $resource = new Resource(
            new DiscoveredUri("http://example.com/test", 0),
            new Response(200, [], "Test Body")
        );
        
        // This will fail because $tmpFile is a file, not a directory, so we can't write inside it
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to write JSON file');
        
        try {
            $handler->persist($resource);
        } finally {
            // Clean up
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            if (is_dir($tmpDir)) {
                rmdir($tmpDir);
            }
        }
    }
}

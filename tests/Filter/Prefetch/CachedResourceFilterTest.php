<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@php-spider.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Filter\Prefetch;

use PHPUnit\Framework\Attributes\CoversMethod;
use GuzzleHttp\Psr7\Response;
use InvalidArgumentException;
use VDB\Spider\Filter\Prefetch\CachedResourceFilter;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Uri;

/**
 * Test for CachedResourceFilter
 */
#[CoversMethod(\VDB\Spider\Filter\Prefetch\CachedResourceFilter::class, '__construct')]
#[CoversMethod(\VDB\Spider\Filter\Prefetch\CachedResourceFilter::class, 'match')]
class CachedResourceFilterTest extends TestCase
{
    private string $testCacheDir;
    private string $testSpiderId = 'test-spider-id';

    protected function setUp(): void
    {
        parent::setUp();
        // Create a temporary cache directory for tests
        $this->testCacheDir = sys_get_temp_dir() . '/php-spider-test-' . uniqid();
        mkdir($this->testCacheDir, 0777, true);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Clean up the test cache directory
        if (is_dir($this->testCacheDir)) {
            $this->removeDirectory($this->testCacheDir);
        }
    }

    public function testMatchReturnsFalseWhenFileDoesNotExist()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/page.html');

        $this->assertFalse($filter->match($uri));
    }

    public function testMatchReturnsTrueWhenFileExistsAndIsFresh()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file
        $this->createCachedFile('example.com/page.html', 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    public function testMatchReturnsFalseWhenFileIsExpired()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 10);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file and modify its timestamp to be older than maxAge
        $filePath = $this->createCachedFile('example.com/page.html', 'test content');
        touch($filePath, time() - 20); // Make it 20 seconds old
        
        $this->assertFalse($filter->match($uri));
    }

    public function testMatchReturnsTrueWhenMaxAgeIsZero()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 0);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file with an old timestamp
        $filePath = $this->createCachedFile('example.com/page.html', 'test content');
        touch($filePath, time() - 86400); // Make it 1 day old
        
        // With maxAge = 0, it should always match if file exists
        $this->assertTrue($filter->match($uri));
    }

    public function testMatchHandlesIndexFiles()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/');

        // Create the cached file (should be stored as index.html)
        $this->createCachedFile('example.com/index.html', 'test content');

        $this->assertTrue($filter->match($uri));
    }

    public function testMatchHandlesRootWithoutTrailingSlash()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com');

        // Create the cached file (should be stored as index.html for empty path)
        $this->createCachedFile('example.com/index.html', 'test content');

        $this->assertTrue($filter->match($uri));
    }

    public function testMatchHandlesDirectoryWithTrailingSlash()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/subdir/');
        
        // Create the cached file (should be stored as index.html in subdir)
        $this->createCachedFile('example.com/subdir/index.html', 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    public function testMatchHandlesSpecialCharactersInFilename()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/file with spaces.html');
        
        // Create the cached file (filename should be URL-encoded)
        $this->createCachedFile('example.com/file+with+spaces.html', 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    public function testMatchWithDifferentSpiderIds()
    {
        $spiderId1 = 'spider-1';
        $spiderId2 = 'spider-2';
        
        $filter1 = new CachedResourceFilter($this->testCacheDir, $spiderId1, 3600);
        $filter2 = new CachedResourceFilter($this->testCacheDir, $spiderId2, 3600);
        
        $uri = new Uri('http://example.com/page.html');
        
        // Create cached file for spider-1
        $this->createCachedFileWithSpiderId('example.com/page.html', 'test content', $spiderId1);
        
        // Filter with spider-1 should match
        $this->assertTrue($filter1->match($uri));
        
        // Filter with spider-2 should not match (different cache)
        $this->assertFalse($filter2->match($uri));
    }

    public function testMatchAtExactMaxAge()
    {
        $maxAge = 10;
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, $maxAge);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file and set it exactly at max age
        $filePath = $this->createCachedFile('example.com/page.html', 'test content');
        touch($filePath, time() - $maxAge);
        
        // At exactly max age, it should be considered expired (age < maxAge is false)
        $this->assertFalse($filter->match($uri));
    }

    public function testConstructorHandlesTrailingSlashInBasePath()
    {
        // Test that constructor properly handles basePath with trailing slash
        $basePathWithSlash = $this->testCacheDir . DIRECTORY_SEPARATOR;
        $filter = new CachedResourceFilter($basePathWithSlash, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/page.html');

        // Create the cached file
        $this->createCachedFile('example.com/page.html', 'test content');

        // Should still match even with trailing slash in constructor
        $this->assertTrue($filter->match($uri));
    }


    public function testConstructorThrowsExceptionForNegativeMaxAge()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('maxAgeSeconds must be greater than or equal to 0');
        new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, -1);
    }

    public function testMatchHandlesAlreadyEncodedFilename()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/a%20b.html');

        $this->createCachedFile('example.com/a%2520b.html', 'test content');

        $this->assertTrue($filter->match($uri));
    }

    public function testPersistedResourceCanBeFoundByCacheFilter()
    {
        $handler = new FileSerializedResourcePersistenceHandler($this->testCacheDir);
        $handler->setSpiderId($this->testSpiderId);
        $handler->persist(
            new Resource(
                new DiscoveredUri('http://example.com/nested/page name.html', 0),
                new Response(200, [], 'test content')
            )
        );

        $expectedFilePath = $this->testCacheDir . DIRECTORY_SEPARATOR
            . $this->testSpiderId . DIRECTORY_SEPARATOR
            . 'example.com/nested/page+name.html';
        $this->assertFileExists($expectedFilePath);

        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $this->assertTrue($filter->match(new Uri('http://example.com/nested/page name.html')));
    }

    /**
     * Creates a cache fixture at an independently specified expected path.
     */
    private function createCachedFile(string $relativePath, string $content): string
    {
        return $this->createCachedFileWithSpiderId($relativePath, $content, $this->testSpiderId);
    }

    /**
     * Creates a cache fixture at an independently specified expected path.
     */
    private function createCachedFileWithSpiderId(string $relativePath, string $content, string $spiderId): string
    {
        $filePath = $this->testCacheDir . DIRECTORY_SEPARATOR . $spiderId
            . DIRECTORY_SEPARATOR . $relativePath;
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($filePath, $content);

        return $filePath;
    }

    /**
     * Recursively remove a directory
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $scannedFiles = scandir($dir);
        if ($scannedFiles === false) {
            // Directory cannot be read, skip removal
            return;
        }
        
        $files = array_diff($scannedFiles, array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

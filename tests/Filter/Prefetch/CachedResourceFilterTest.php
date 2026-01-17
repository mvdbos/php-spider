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

use VDB\Spider\Filter\Prefetch\CachedResourceFilter;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;

/**
 * Test for CachedResourceFilter
 */
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

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchReturnsFalseWhenFileDoesNotExist()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/page.html');
        
        $this->assertFalse($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchReturnsTrueWhenFileExistsAndIsFresh()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file
        $this->createCachedFile($uri, 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchReturnsFalseWhenFileIsExpired()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 10);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file and modify its timestamp to be older than maxAge
        $filePath = $this->createCachedFile($uri, 'test content');
        touch($filePath, time() - 20); // Make it 20 seconds old
        
        $this->assertFalse($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchReturnsTrueWhenMaxAgeIsZero()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 0);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file with an old timestamp
        $filePath = $this->createCachedFile($uri, 'test content');
        touch($filePath, time() - 86400); // Make it 1 day old
        
        // With maxAge = 0, it should always match if file exists
        $this->assertTrue($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchHandlesIndexFiles()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/');
        
        // Create the cached file (should be stored as index.html)
        $this->createCachedFile($uri, 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchHandlesDirectoryWithTrailingSlash()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/subdir/');
        
        // Create the cached file (should be stored as index.html in subdir)
        $this->createCachedFile($uri, 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchHandlesSpecialCharactersInFilename()
    {
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, 3600);
        $uri = new Uri('http://example.com/file with spaces.html');
        
        // Create the cached file (filename should be URL-encoded)
        $this->createCachedFile($uri, 'test content');
        
        $this->assertTrue($filter->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchWithDifferentSpiderIds()
    {
        $spiderId1 = 'spider-1';
        $spiderId2 = 'spider-2';
        
        $filter1 = new CachedResourceFilter($this->testCacheDir, $spiderId1, 3600);
        $filter2 = new CachedResourceFilter($this->testCacheDir, $spiderId2, 3600);
        
        $uri = new Uri('http://example.com/page.html');
        
        // Create cached file for spider-1
        $this->createCachedFileWithSpiderId($uri, 'test content', $spiderId1);
        
        // Filter with spider-1 should match
        $this->assertTrue($filter1->match($uri));
        
        // Filter with spider-2 should not match (different cache)
        $this->assertFalse($filter2->match($uri));
    }

    /**
     * @covers \VDB\Spider\Filter\Prefetch\CachedResourceFilter
     */
    public function testMatchAtExactMaxAge()
    {
        $maxAge = 10;
        $filter = new CachedResourceFilter($this->testCacheDir, $this->testSpiderId, $maxAge);
        $uri = new Uri('http://example.com/page.html');
        
        // Create the cached file and set it exactly at max age
        $filePath = $this->createCachedFile($uri, 'test content');
        touch($filePath, time() - $maxAge);
        
        // At exactly max age, it should be considered expired (age < maxAge is false)
        $this->assertFalse($filter->match($uri));
    }

    /**
     * Helper method to create a cached file for testing
     */
    private function createCachedFile(Uri $uri, string $content): string
    {
        return $this->createCachedFileWithSpiderId($uri, $content, $this->testSpiderId);
    }

    /**
     * Helper method to create a cached file with a specific spider ID
     */
    private function createCachedFileWithSpiderId(Uri $uri, string $content, string $spiderId): string
    {
        $hostname = $uri->getHost();
        $path = $uri->getPath() ?: '';
        
        // Complete path with default filename if needed
        if ($path === '') {
            $path = "/index.html";
        } elseif (substr($path, -1, 1) === '/') {
            $path .= "index.html";
        }
        
        // Build the directory structure
        $directory = $this->testCacheDir . DIRECTORY_SEPARATOR . 
                    $spiderId . DIRECTORY_SEPARATOR . 
                    $hostname . dirname($path);
        
        // Create directory if it doesn't exist
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        
        // The filename is URL-encoded
        $filename = urlencode(basename($path));
        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;
        
        // Write the content
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
        
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}

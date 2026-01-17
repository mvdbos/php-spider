<?php

namespace VDB\Spider\Filter\Prefetch;

use VDB\Spider\Filter\PreFetchFilterInterface;
use VDB\Uri\UriInterface;

/**
 * Filter to skip downloading resources that are already cached and younger than max age.
 * 
 * This filter checks if a resource has been previously downloaded and persisted to disk.
 * If the cached file exists and its modification time is within the specified max age,
 * the filter returns true to skip re-downloading.
 * 
 * Note: This requires using the same spider ID across runs to share the cache directory.
 * 
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 */
class CachedResourceFilter implements PreFetchFilterInterface
{
    private string $basePath;
    private string $spiderId;
    private int $maxAgeSeconds;
    private string $defaultFilename = 'index.html';

    /**
     * @param string $basePath The base directory where spider results are stored
     * @param string $spiderId The spider ID used for the cache directory
     * @param int $maxAgeSeconds Maximum age in seconds for cached resources (0 = always use cache)
     */
    public function __construct(string $basePath, string $spiderId, int $maxAgeSeconds = 0)
    {
        $this->basePath = rtrim($basePath, DIRECTORY_SEPARATOR);
        $this->spiderId = $spiderId;
        $this->maxAgeSeconds = $maxAgeSeconds;
    }

    /**
     * Returns true if the URI should be filtered out (already cached and fresh).
     * 
     * @param UriInterface $uri
     * @return bool
     */
    public function match(UriInterface $uri): bool
    {
        $filePath = $this->getFilePath($uri);
        
        if (!file_exists($filePath)) {
            return false;
        }

        // If maxAgeSeconds is 0, always use cache
        if ($this->maxAgeSeconds === 0) {
            return true;
        }

        $fileModTime = filemtime($filePath);
        $currentTime = time();
        $age = $currentTime - $fileModTime;

        // Return true if file is younger than max age (skip download)
        return $age < $this->maxAgeSeconds;
    }

    /**
     * Build the file path for a given URI, mirroring the FilePersistenceHandler logic.
     * 
     * @param UriInterface $uri
     * @return string
     */
    private function getFilePath(UriInterface $uri): string
    {
        $hostname = $uri->getHost();
        $path = $uri->getPath() ?: '';
        
        // Complete path with default filename if needed
        $fullPath = $this->completePath($path);
        
        // Build the directory structure: {basePath}/{spiderId}/{hostname}{dirname}
        $directory = $this->basePath . DIRECTORY_SEPARATOR . 
                    $this->spiderId . DIRECTORY_SEPARATOR . 
                    $hostname . dirname($fullPath);
        
        // The filename is URL-encoded
        $filename = urlencode(basename($fullPath));
        
        return $directory . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Complete a path with default filename if it ends with a slash.
     * This mirrors the FilePersistenceHandler::completePath() logic.
     * 
     * @param string $path
     * @return string
     */
    private function completePath(string $path): string
    {
        if ($path === '') {
            $path = "/" . $this->defaultFilename;
        } elseif (substr($path, -1, 1) === '/') {
            $path .= $this->defaultFilename;
        }

        return $path;
    }
}

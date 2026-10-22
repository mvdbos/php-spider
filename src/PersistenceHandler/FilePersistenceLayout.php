<?php

namespace VDB\Spider\PersistenceHandler;

final class FilePersistenceLayout
{
    public static function getResultPath(string $path, string $spiderId): string
    {
        return $path . DIRECTORY_SEPARATOR . $spiderId . DIRECTORY_SEPARATOR;
    }

    public static function completePath(?string $path, string $defaultFilename): string
    {
        if ($path == '') {
            $path = "/" . $defaultFilename;
        } elseif (substr($path, -1, 1) === '/') {
            $path .= $defaultFilename;
        }

        return $path;
    }

    public static function getFileSystemFilename(?string $path, string $defaultFilename): string
    {
        return self::getFileSystemFilenameFromCompletePath(self::completePath($path, $defaultFilename));
    }

    public static function getFileSystemFilenameFromCompletePath(string $path): string
    {
        return urlencode(basename($path));
    }

    public static function getFileSystemPath(
        ?string $hostname,
        ?string $path,
        string $defaultFilename
    ): string {
        return self::getFileSystemPathFromCompletePath(
            $hostname,
            self::completePath($path, $defaultFilename)
        );
    }

    public static function getFileSystemPathFromCompletePath(?string $hostname, string $path): string
    {
        return $hostname . dirname($path);
    }

    public static function getResourceFilePath(
        string $basePath,
        string $spiderId,
        ?string $hostname,
        ?string $path,
        string $defaultFilename
    ): string {
        return self::getResultPath($basePath, $spiderId)
            . self::getFileSystemPath($hostname, $path, $defaultFilename)
            . DIRECTORY_SEPARATOR
            . self::getFileSystemFilename($path, $defaultFilename);
    }
}

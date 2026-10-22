<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\PersistenceHandler;

use Exception;
use Iterator;
use ReturnTypeWillChange; // @phan-suppress-current-line PhanUnreferencedUseNormal
use Symfony\Component\Finder\Finder;
use VDB\Spider\Resource;

abstract class FilePersistenceHandler implements PersistenceHandlerInterface
{
    /**
     * @var string the path where all spider results should be persisted.
     *             The results will be grouped in a directory by spider ID.
     */
    protected string $path = '';

    protected string $spiderId = '';

    protected int $totalSizePersisted = 0;

    protected Iterator $iterator;

    protected ?Finder $finder = null;

    /**
     * @var int|null Cached number of persisted resources. Null means "not counted yet".
     * Counting through the Finder walks the whole directory tree, which would be
     * quadratic when the download limit is checked on every iteration of the crawl loop.
     */
    private ?int $resourceCount = null;

    /** @var string The filename that will be appended for resources that end with a slash */
    protected string $defaultFilename = 'index.html';

    /**
     * @param string $path the path where all spider results should be persisted.
     *        The results will be grouped in a directory by spider ID.
     */
    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function setSpiderId(string $spiderId): void
    {
        $this->spiderId = $spiderId;

        // create the path
        if (!file_exists($this->getResultPath())) {
            mkdir($this->getResultPath(), 0700, true);
        }
    }

    protected function getResultPath(): string
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->spiderId . DIRECTORY_SEPARATOR;
    }

    public function count(): int
    {
        if ($this->resourceCount === null) {
            $this->resourceCount = $this->getFinder()->count();
        }
        return $this->resourceCount;
    }

    /**
     * Keeps the cached count in sync with persist().
     * Implementations of persist() should call this after a resource has been persisted.
     */
    protected function markPersisted(): void
    {
        if ($this->resourceCount !== null) {
            $this->resourceCount++;
        }
    }

    /**
     * @return Finder
     */
    protected function getFinder(): Finder
    {
        if ($this->finder == null) {
            $this->finder = Finder::create()->files()->in($this->getResultPath());
        }
        return $this->finder;
    }

    abstract public function persist(Resource $resource);

    /**
     * @return Resource
     */
    abstract public function current(): Resource;

    /**
     * @return void
     * @throws Exception
     */
    public function next(): void
    {
        $this->getIterator()->next();
    }

    /**
     * @return Iterator
     * @throws Exception
     */
    protected function getIterator(): Iterator
    {
        if (!isset($this->iterator)) {
            $this->iterator = $this->getFinder()->getIterator();
        }
        return $this->iterator;
    }

    /**
     * @return integer|double|string|boolean|null
     * @throws Exception
     */
    #[ReturnTypeWillChange] // @phan-suppress-current-line PhanUndeclaredClassAttribute
    public function key(): float|bool|int|string|null
    {
        return $this->getIterator()->key();
    }

    /**
     * @return boolean
     * @throws Exception
     */
    public function valid(): bool
    {
        return $this->getIterator()->valid();
    }

    /**
     * @return void
     * @throws Exception
     */
    public function rewind(): void
    {
        $this->getIterator()->rewind();
    }

    protected function getFileSystemFilename(Resource $resource): string
    {
        $fullPath = $this->completePath($resource->getUri()->getPath());

        return urlencode(basename($fullPath));
    }

    /**
     * @param string|null $path
     * @return string The path that was provided with a default filename appended if it is
     *         a path ending in a /. This is because we don't want to persist
     *         the directories as files. This is similar to wget behaviour.
     */
    protected function completePath(?string $path): string
    {
        if ($path == '') {
            $path = "/" . $this->defaultFilename;
        } elseif (substr($path, -1, 1) === '/') {
            $path .= $this->defaultFilename;
        }

        return $path;
    }

    protected function getFileSystemPath(Resource $resource): string
    {
        $hostname = $resource->getUri()->getHost();
        $fullPath = $this->completePath($resource->getUri()->getPath());

        return $hostname . dirname($fullPath);
    }
}

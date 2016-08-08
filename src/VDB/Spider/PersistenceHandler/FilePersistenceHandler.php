<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use Symfony\Component\Finder\Finder;
use VDB\Spider\Resource;

abstract class FilePersistenceHandler implements PersistenceHandlerInterface
{
    /**
     * @var string the path where all spider results should be persisted.
     *             The results will be grouped in a directory by spider ID.
     */
    protected $path = '';

    protected $spiderId = '';

    protected $totalSizePersisted = 0;

    /** @var \Iterator */
    protected $iterator;

    /** @var Finder */
    protected $finder;

    /** @var string The filename that will be appended for resources that end with a slash */
    protected $defaultFilename = 'index.html';

    /**
     * @param string $path the path where all spider results should be persisted.
     *        The results will be grouped in a directory by spider ID.
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function setSpiderId($spiderId)
    {
        $this->spiderId = $spiderId;

        // create the path
        if (!file_exists($this->getResultPath())) {
            mkdir($this->getResultPath(), 0700, true);
        }
    }

    protected function getFileSystemFilename($resource)
    {
        $fullPath = $this->completePath($resource->getUri()->getPath());

        return urlencode(basename($fullPath));
    }

    protected function getFileSystemPath($resource)
    {
        $hostname = $resource->getUri()->getHost();
        $fullPath = $this->completePath($resource->getUri()->getPath());

        return $hostname . dirname($fullPath);
    }

    /**
     * @return The path that was provided with a default filenameappended if it is
     *         a path ending in a /. This is because we don't want to persist
     *         the directories as files. This is similar to wget behaviour.
     */
    protected function completePath($path)
    {
        if (substr($path, -1, 1) === '/') {
            $path .= $this->defaultFilename;
        }

        return $path;
    }

    public function count()
    {
        return $this->getFinder()->count();
    }

    protected function getResultPath()
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->spiderId . DIRECTORY_SEPARATOR;
    }

    abstract public function persist(Resource $resource);

    /**
     * @return Finder
     */
    protected function getFinder()
    {
        if (!$this->finder instanceof Finder) {
            $this->finder = Finder::create()->files()->in($this->getResultPath());
        }
        return $this->finder;
    }

    /**
     * @return \Iterator
     */
    protected function getIterator()
    {
        if (!$this->iterator instanceof \Iterator) {
            $this->iterator = $this->getFinder()->getIterator();
        }
        return $this->iterator;
    }

    /**
     * @return Resource
     */
    abstract public function current();

    /**
     * @return void
     */
    public function next()
    {
        $this->getIterator()->next();
    }

    /**
     * @return integer|double|string|boolean|null
     */
    public function key()
    {
        return $this->getIterator()->key();
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return $this->getIterator()->valid();
    }

    /**
     * @return void
     */
    public function rewind()
    {
        $this->getIterator()->rewind();
    }
}

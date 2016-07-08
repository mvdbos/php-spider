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
     * Encodes file name into suitable format and trims its size.
     * File name length is limited 255 chars, on Win32 a whole file path is limited 260 chars.
     * @param string $fileName
     * @return string
     */
    protected function encodeFileName($fileName)
    {
        return md5($fileName);
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

<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use Symfony\Component\Finder\Finder;
use VDB\Spider\Resource;

class FileRawResponsePersistenceHandler implements PersistenceHandler, \Iterator
{
    /**
     * @var string the path where all spider results should be persisted.
     *             The results will be grouped in a directory by spider ID.
     */
    private $path = '';

    private $spiderId = '';

    private $totalSizePersisted = 0;

    /** @var \Iterator */
    private $iterator;

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

    private function getResultPath()
    {
        return $this->path . DIRECTORY_SEPARATOR . $this->spiderId . DIRECTORY_SEPARATOR;
    }

    public function persist(Resource $resource)
    {
        $fileName = urlencode($resource->getUri()->toString());
        $file = new \SplFileObject($this->getResultPath() . $fileName, 'w');
        $rawResponse = $resource->getResponse()->__toString();
        $this->totalSizePersisted += $file->fwrite($rawResponse);
    }

    private function getIterator()
    {
        if (!$this->iterator instanceof \Iterator) {
            $finder = Finder::create()->files()->in($this->getResultPath());
            $this->iterator = $finder->getIterator();
        }
        return $this->iterator;
    }

    /**
     * @return Resource
     */
    public function current()
    {
        return $this->getIterator()->current()->getContents();
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->getIterator()->next();
    }

    /**
     * @return int
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

<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use VDB\Spider\Resource;

class MemoryPersistenceHandler implements PersistenceHandler, \Iterator
{
    private $resources = array();

    public function setSpiderId($spiderId)
    {
        // memory handler ignores this. Only interesting for true persistence as some kind of key or prefix
    }

    public function persist(Resource $resource)
    {
        $this->resources[] = $resource;
    }

    /**
     * @return Resource
     */
    public function current()
    {
        return current($this->resources);
    }

    /**
     * @return void
     */
    public function next()
    {
        next($this->resources);
    }

    /**
     * @return int
     */
    public function key()
    {
        return key($this->resources);
    }

    /**
     * @return boolean
     */
    public function valid()
    {
        return (bool)current($this->resources);
    }

    /**
     * @return void
     */
    public function rewind()
    {
        reset($this->resources);
    }
}

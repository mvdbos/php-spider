<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use VDB\Spider\Resource;

interface PersistenceHandlerInterface extends \Iterator, \Countable
{
    /**
     * @param string $spiderId
     *
     * @return void
     */
    public function setSpiderId(string $spiderId);

    /**
     * @param Resource $resource
     * @return void
     */
    public function persist(Resource $resource);
}

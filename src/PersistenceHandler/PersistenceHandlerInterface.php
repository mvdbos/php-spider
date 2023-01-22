<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\PersistenceHandler;

use Countable;
use Iterator;
use VDB\Spider\Resource;

interface PersistenceHandlerInterface extends Iterator, Countable
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

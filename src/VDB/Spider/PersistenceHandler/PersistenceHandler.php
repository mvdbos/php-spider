<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use VDB\Spider\Resource;

interface PersistenceHandler
{
    public function setSpiderId($spiderId);

    public function persist(Resource $resource);
}

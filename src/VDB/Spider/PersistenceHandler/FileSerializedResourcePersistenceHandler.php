<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use Symfony\Component\Finder\Finder;
use VDB\Spider\PersistenceHandler\FilePersistenceHandler;
use VDB\Spider\Resource;

class FileSerializedResourcePersistenceHandler extends FilePersistenceHandler implements PersistenceHandlerInterface
{
    public function persist(Resource $resource)
    {
        $fileName = $this->encodeFileName($resource->getUri()->toString());
        $file = new \SplFileObject($this->getResultPath() . $fileName, 'w');
        $this->totalSizePersisted += $file->fwrite(serialize($resource));
    }

    /**
     * @return Resource
     */
    public function current()
    {
        return unserialize($this->getIterator()->current()->getContents());
    }
}

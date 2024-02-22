<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */

namespace VDB\Spider\PersistenceHandler;

use Exception;
use SplFileObject;
use VDB\Spider\Resource;

class FileSerializedResourcePersistenceHandler extends FilePersistenceHandler implements PersistenceHandlerInterface
{
    public function persist(Resource $resource): bool
    {
        $path = $this->getResultPath() . $this->getFileSystemPath($resource);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
        try {
            $file = new SplFileObject($path . DIRECTORY_SEPARATOR . $this->getFileSystemFilename($resource), 'w');
            $this->totalSizePersisted += $file->fwrite(serialize($resource));
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * @return Resource
     * @throws Exception
     */
    public function current(): Resource
    {
        return unserialize($this->getIterator()->current()->getContents());
    }
}

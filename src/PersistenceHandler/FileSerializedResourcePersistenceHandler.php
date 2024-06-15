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
    public function persist(Resource $resource): void
    {
        $path = $this->getResultPath() . $this->getFileSystemPath($resource);
        if (!file_exists($path)) {
            if (!mkdir($path, 0777, true)) {
                $this->getLogger()->warning("Failed to create directory: $path");
            }
        }
        $file = new SplFileObject($path . DIRECTORY_SEPARATOR . $this->getFileSystemFilename($resource), 'w');
        $this->totalSizePersisted += $file->fwrite(serialize($resource));
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

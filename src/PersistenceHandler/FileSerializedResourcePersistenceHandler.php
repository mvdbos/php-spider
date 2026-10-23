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
    public function persist(Resource $resource)
    {
        $path = $this->getResultPath() . $this->getFileSystemPath($resource);
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }
        $filePath = $path . DIRECTORY_SEPARATOR . $this->getFileSystemFilename($resource);
        $isNewFile = !file_exists($filePath);
        $file = new SplFileObject($filePath, 'w');
        $this->totalSizePersisted += $file->fwrite(serialize($resource));
        if ($isNewFile) {
            $this->markPersisted();
        }
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

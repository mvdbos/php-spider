<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace VDB\Spider\PersistenceHandler;

use Symfony\Component\Finder\Finder;
use VDB\Spider\PersistenceHandler\FilePersistenceHandler;
use VDB\Spider\Resource;

class FileRawResponsePersistenceHandler extends FilePersistenceHandler implements PersistenceHandlerInterface
{
    public function persist(Resource $resource)
    {
        $fileName = urlencode($resource->getUri()->toString());
        $file = new \SplFileObject($this->getResultPath() . $fileName, 'w');
        $rawResponse = $resource->getResponse()->__toString();
        $this->totalSizePersisted += $file->fwrite($rawResponse);
    }

    /**
     * @return Resource
     */
    public function current()
    {
        return $this->getIterator()->current()->getContents();
    }
}

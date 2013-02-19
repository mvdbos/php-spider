<?php
namespace Example\Processors;

use VDB\Spider\Processor;
use VDB\Spider\Document;

/**
 * @author matthijs
 */
class ImageCounterProcessor implements Processor
{
    private $imageCounter = array();

    /**
     * @param string $uri
     * @return boolean
     */
    public function execute(Document $document)
    {


        $images = $document->getCrawler()->filterXPath('//img')->extract(array('src'));
        $imageCount = array();
        if (count($images)) {
            foreach ($images as $image) {
                $imageCount[] = $image;
            }
        }
        $this->imageCounter[$document->getUri()->getUri()] = $imageCount;
    }

    public function getImageCount()
    {
        return $this->imageCounter;
    }
}
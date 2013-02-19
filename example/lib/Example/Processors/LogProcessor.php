<?php
namespace Example\Processors;

use VDB\Spider\Processor;
use VDB\Spider\Document;

/**
 * @author matthijs
 */
class LogProcessor implements Processor
{
    public $visited = array();

    /**
     * @param string $uri
     * @return boolean
     */
    public function execute(Document $document)
    {
        $this->visited[$document->getUri()->getUri()] = true;

//        $html = '';
//        foreach ($document->getCrawler() as $domElement) {
//            $html.= $domElement->ownerDocument->saveHTML();
//        }
    }
}
<?php
namespace VDB\Spider\Tests\Fixtures;

use VDB\Spider\Processor;
use VDB\Spider\Document;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
class TitleExtractorProcessor implements Processor
{
    public $titles = '';

    /**
     * @param string $uri
     * @return void
     */
    public function execute(Document $document)
    {
        $this->titles .= $document->getCrawler()->filterXPath('//title')->text();
    }
}

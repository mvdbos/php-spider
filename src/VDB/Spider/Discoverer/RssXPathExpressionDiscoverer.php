<?php
namespace VDB\Spider\Discoverer;

use VDB\Spider\Discoverer;
use VDB\Spider\Spider;
use VDB\URI\GenericURI;
use VDB\Spider\Document;
use VDB\URI\URI;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class RssXPathExpressionDiscoverer implements Discoverer
{
    /** @var string */
    protected $xpathExpression;

    /**
     * @param $xpathExpression
     */
    public function __construct($xpathExpression)
    {
        $this->xpathExpression = $xpathExpression;
    }

    /**
     * @param \VDB\Spider\Document $document
     * @return URI[]
     */
    public function discover(Spider $spider, Document $document)
    {
        $crawler = $document->getCrawler()->filterXPath($this->xpathExpression);
        $uris = array();
        foreach ($crawler as $node) {
            try {
                $uris[] = new GenericURI($node->getAttribute('href'), $document->getUri()->recompose());
            } catch (UriSyntaxException $e) {
                $spider->addToFailed($node->getAttribute('href'), 'Invalid URI: ' . $e->getMessage());
            }
        }
        return $uris;
    }
}

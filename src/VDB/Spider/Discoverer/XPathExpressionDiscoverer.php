<?php
namespace VDB\Spider\Discoverer;

use VDB\Spider\Discoverer\Discoverer;
use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\URI\Exception\UriSyntaxException;
use VDB\URI\GenericURI;
use VDB\URI\URI;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class XPathExpressionDiscoverer implements Discoverer
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
     * @param Spider $spider
     * @param Resource $document
     * @return URI[]
     */
    public function discover(Spider $spider, Resource $document)
    {
        $crawler = $document->getCrawler()->filterXPath($this->xpathExpression);
        $uris = array();
        foreach ($crawler as $node) {
            try {
                $uris[] = new GenericURI($node->getAttribute('href'), $document->getUri()->toString());
            } catch (UriSyntaxException $e) {
                $spider->addToFailed($node->getAttribute('href'), 'Invalid URI: ' . $e->getMessage());
            }
        }
        return $uris;
    }
}

<?php
namespace VDB\Spider\Discoverer;

use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Discoverer\Discoverer;
use VDB\Spider\Resource;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class XPathExpressionDiscoverer extends Discoverer implements DiscovererInterface
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
     * @param Resource $resource
     * @return Uri[]
     */
    public function discover(Resource $resource)
    {
        $crawler = $resource->getCrawler()->filterXPath($this->xpathExpression);
        $uris = array();
        foreach ($crawler as $node) {
            try {
                $uris[] = new Uri($node->getAttribute('href'), $resource->getUri()->toString());
            } catch (UriSyntaxException $e) {
                // do nothing. We simply ignore invalid URI's
            }
        }
        return $uris;
    }
}

<?php

namespace VDB\Spider\Discoverer;

use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;

/**
 * Simple XPath discoverer that only accepts selectors ending with '/a'.
 *
 * For XPath expressions with predicates on anchor elements (square-bracket
 * notation on the anchor), use XPathExpressionDiscoverer instead.
 *
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class SimpleXPathExpressionDiscoverer extends CrawlerDiscoverer
{
    /**
     * Set the XPath selector to use.
     *
     * This selector should look for `a` elements so that the Discoverer can
     * extract their `href` attribute for further crawling.
     *
     * @param string $selector
     * @throws InvalidArgumentException
     */
    public function __construct(string $selector)
    {
        if (!self::endsWith($selector, "/a")) {
            throw new InvalidArgumentException(
                "SimpleXPathExpressionDiscoverer selectors must target anchor ('a') elements and " .
                "must end with '/a' so that the Discoverer can extract their `href` attribute for further crawling."
            );
        }
        parent::__construct($selector);
    }

    protected function getFilteredCrawler(Resource $resource): Crawler
    {
        return $resource->getCrawler()->filterXPath($this->selector);
    }

    private static function endsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        return substr($haystack, -$length) === $needle;
    }
}

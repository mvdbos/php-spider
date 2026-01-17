<?php

namespace VDB\Spider\Discoverer;

use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;

/**
 * XPath discoverer that supports advanced XPath expressions including predicates (square-bracket notation).
 * 
 * This discoverer validates that the selector targets anchor elements, but allows
 * for more complex XPath expressions such as:
 * - //a[starts-with(@href, '/')]
 * - //div[@id='content']//a
 * - //a[@class='internal']
 * 
 * For simple selectors that just end with '/a', you may also use SimpleXPathExpressionDiscoverer.
 * 
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class XPathExpressionDiscoverer extends CrawlerDiscoverer
{
    /**
     * Set the XPath selector to use.
     *
     * This selector should look for `a` elements so that the Discoverer can
     * extract their `href` attribute for further crawling.
     * 
     * Supports XPath predicates (square-bracket notation) such as:
     * - //a[starts-with(@href, '/')]
     * - //div[@id='content']//a[@class='link']
     *
     * @param string $selector
     * @throws InvalidArgumentException
     */
    public function __construct(string $selector)
    {
        if (!$this->validateSelector($selector)) {
            throw new InvalidArgumentException(
                "The selector must target anchor ('a') elements. " .
                "Valid examples: '//a', '//div[@id=\"content\"]//a', '//a[starts-with(@href, \"/\")]'"
            );
        }
        parent::__construct($selector);
    }

    protected function getFilteredCrawler(Resource $resource): Crawler
    {
        return $resource->getCrawler()->filterXPath($this->selector);
    }

    /**
     * Validates that the selector targets anchor elements.
     * 
     * Accepts selectors that:
     * - End with '/a' (simple case)
     * - End with '/a[...]' (with predicates)
     * - Contain '//a[' (anchor with predicates anywhere in the path)
     * - Contain '//a/' (anchor followed by more path)
     * 
     * @param string $selector
     * @return bool
     */
    protected function validateSelector(string $selector): bool
    {
        // Match patterns that indicate the selector targets anchor elements
        return preg_match('#/a(\[|/|$)#', $selector) === 1;
    }
}

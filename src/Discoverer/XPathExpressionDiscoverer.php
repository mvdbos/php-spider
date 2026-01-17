<?php

namespace VDB\Spider\Discoverer;

use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;

/**
 * XPath discoverer that supports advanced XPath expressions including predicates (square-bracket notation).
 * 
 * This discoverer validates that the selector targets anchor elements as the final selected element,
 * but allows for more complex XPath expressions such as:
 * - //a[starts-with(@href, '/')]
 * - //div[@id='content']//a
 * - //a[@class='internal']
 * 
 * For selectors where the anchor element has no predicates (for example, //div[@id='content']//a),
 * you may also use SimpleXPathExpressionDiscoverer.
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
     * Validates that the selector targets anchor elements as the final selected element.
     * 
     * Accepts selectors that:
     * - End with '//a' (simple case)
     * - End with '//a[...]' (with predicates on the anchor)
     * 
     * Rejects selectors where anchor is not the final element:
     * - '//a//span' (selects span, not anchor)
     * - '//a/text()' (selects text node, not anchor)
     * 
     * @param string $selector
     * @return bool
     */
    protected function validateSelector(string $selector): bool
    {
        // Match patterns that indicate the selector targets anchor elements as final element
        // Ensures //a is present, optionally followed by predicates [...], and then end of string
        return preg_match('#//a(\[[^\]]*\])*$#', $selector) === 1;
    }
}

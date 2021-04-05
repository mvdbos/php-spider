<?php

namespace VDB\Spider\Discoverer;

use InvalidArgumentException;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;

/**
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
     * @param string $selector
     * @throws InvalidArgumentException
     */
    public function __construct(string $selector)
    {
        if (!self::endsWith($selector, "/a")) {
            throw new InvalidArgumentException("Please end your selector with '/a': " .
                "selectors should look for `a` elements " .
                "so that the Discoverer can extract their `href` attribute for further crawling.");
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

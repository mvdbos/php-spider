<?php

namespace VDB\Spider\Discoverer;

use Exception;
use VDB\Spider\Resource;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
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
     * @throws Exception
     */
    public function __construct(string $selector)
    {
        if (!self::endsWith($selector, "/a")) {
            throw new Exception("Please end your selector with '/a': " .
                "selectors should look for `a` elements " .
                "so that the Discoverer can extract their `href` attribute for further crawling.");
        }
        parent::__construct($selector);
    }

    protected function getFilteredCrawler(Resource $resource)
    {
        return $resource->getCrawler()->filterXPath($this->selector);
    }

    private static function endsWith($haystack, $needle): bool
    {
        $length = strlen($needle);
        if (!$length) {
            return true;
        }
        return substr($haystack, -$length) === $needle;
    }
}

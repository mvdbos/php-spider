<?php
namespace VDB\Spider\Discoverer;

use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Discoverer\Discoverer;
use VDB\Spider\Resource;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Uri;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
abstract class CrawlerDiscoverer extends Discoverer implements DiscovererInterface
{
    /** @var string */
    protected $selector;

    /**
     * @param $selector
     */
    public function __construct($selector)
    {
        $this->selector = $selector;
    }

    /**
     * @return Crawler
     */
    abstract protected function getFilteredCrawler(Resource $resource);

    /**
     * @param Resource $resource
     * @return DiscoveredUri[]
     */
    public function discover(Resource $resource)
    {
        $crawler = $this->getFilteredCrawler($resource);

        $uris = array();
        foreach ($crawler as $node) {
            try {
                $uris[] = new DiscoveredUri(new Uri($node->getAttribute('href'), $resource->getUri()->toString()));
            } catch (UriSyntaxException $e) {
                // do nothing. We simply ignore invalid URI's
            }
        }
        return $uris;
    }
}

<?php
namespace VDB\Spider\Discoverer;

/* @phan-file-suppress PhanUnreferencedUseNormal */
use DOMElement;
use Symfony\Component\DomCrawler\Crawler;
use Uri\InvalidUriException;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
abstract class CrawlerDiscoverer extends Discoverer implements DiscovererInterface
{
    protected string $selector;

    /**
     * @param string $selector
     */
    public function __construct(string $selector)
    {
        $this->selector = $selector;
    }

    /**
     * @param Resource $resource
     * @return Crawler
     */
    abstract protected function getFilteredCrawler(Resource $resource): Crawler;

    /**
     * @param Resource $resource
     * @return DiscoveredUri[]
     */
    public function discover(Resource $resource): array
    {
        $crawler = $this->getFilteredCrawler($resource);

        $uris = array();
        foreach ($crawler as $node) {
            try {
                // @phan-suppress-next-line PhanUndeclaredMethod - Symfony DomCrawler returns DOMElement instances
                $href = $node->getAttribute('href');
                $depthFound = $resource->getUri()->getDepthFound() + 1;

                $uris[] = new DiscoveredUri($resource->getUri()->resolve($href), $depthFound);
            } catch (InvalidUriException $e) {
                // do nothing. We simply ignore invalid URIs, since we don't control what we crawl.
            }
        }
        return $uris;
    }
}

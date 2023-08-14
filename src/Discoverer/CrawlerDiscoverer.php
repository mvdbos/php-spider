<?php
namespace VDB\Spider\Discoverer;

/* @phan-file-suppress PhanUnreferencedUseNormal */
use DOMElement;
use ErrorException;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Uri\Http;
use VDB\Uri\Uri;

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
     * @throws ErrorException
     */
    public function discover(Resource $resource): array
    {
        $crawler = $this->getFilteredCrawler($resource);

        $uris = array();
        foreach ($crawler as $node) {
            /**@var $node DOMElement */
            try {
                $baseUri = $resource->getUri()->toString();
                $href = $node->getAttribute('href');
                $depthFound = $resource->getUri()->getDepthFound() + 1;

                if (substr($href, 0, 4) === "http") {
                    $uris[] = new DiscoveredUri(new Http($href, $baseUri), $depthFound);
                } else {
                    $uris[] = new DiscoveredUri(new Uri($href, $baseUri), $depthFound);
                }
            } catch (UriSyntaxException $e) {
                // do nothing. We simply ignore invalid URIs, since we don't control what we crawl.
            }
        }
        return $uris;
    }
}

<?php

namespace VDB\Spider;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class Resource
{
    protected DiscoveredUri $uri;
    protected ResponseInterface $response;
    protected ?Crawler $crawler = null;
    protected string $body;

    /**
     * @param DiscoveredUri $uri
     * @param ResponseInterface $response
     */
    public function __construct(DiscoveredUri $uri, ResponseInterface $response)
    {
        $this->uri = $uri;
        $this->response = $response;
    }

    /**
     * Lazy loads a Crawler object based on the ResponseInterface;
     * @return Crawler
     */
    public function getCrawler(): Crawler
    {
        if ($this->crawler == null) {
            $this->crawler = new Crawler(null, $this->getUri()->toString());
            $this->crawler->addContent(
                $this->getResponse()->getBody()->__toString(),
                $this->getResponse()->getHeaderLine('Content-Type')
            );
        }
        return $this->crawler;
    }

    /**
     * @return DiscoveredUri
     */
    public function getUri(): DiscoveredUri
    {
        return $this->uri;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * Returns the effective (final) URI after any redirects.
     *
     * If the Guzzle client was configured with 'track_redirects' => true in the
     * 'allow_redirects' option, this method returns the final URI after following
     * all redirects. Otherwise, it returns the original URI.
     *
     * To enable redirect tracking, configure your GuzzleRequestHandler with:
     * $client = new Client(['allow_redirects' => ['track_redirects' => true]]);
     *
     * @return string The effective URI after redirects, or the original URI if no redirects occurred
     */
    public function getEffectiveUri(): string
    {
        $redirectHistory = $this->response->getHeader('X-Guzzle-Redirect-History');

        if (!empty($redirectHistory)) {
            // Return the last URI in the redirect history (the final destination)
            return end($redirectHistory);
        }

        // No redirects occurred or tracking is not enabled - return original URI
        return $this->uri->toString();
    }

    public function __sleep(): array
    {
        /*
         * Because the Crawler isn't serialized correctly, we exclude it from serialization
         * It will be available again after wakeup through lazy loading with getCrawler()
         */

        // we store the response manually, because otherwise it will not get serialized.
        $this->body = Message::toString($this->response);

        return array(
            'uri',
            'body'
        );
    }

    /**
     * We need to set the body again after deserialization because it was a stream that didn't get serialized
     */
    public function __wakeup()
    {
        $this->response = Message::parseResponse($this->body);
    }
}

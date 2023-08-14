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
            $this->crawler = new Crawler('', $this->getUri()->toString());
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

<?php
namespace VDB\Spider;

use Guzzle\Http\Message\Response;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class Resource
{
    /** @var DiscoveredUri */
    protected $uri;

    /** @var Response */
    protected $response;

    /** @var Crawler */
    protected $crawler;

    /** @var string */
    protected $body;

    /**
    * @param DiscoveredUri $uri
     * @param Response $response
     */
    public function __construct(DiscoveredUri $uri, Response $response)
    {
        $this->uri = $uri;
        $this->response = $response;

        // we store the response manually, because otherwise it will not get serialized. It is a php://temp stream
        $this->body = $response->getBody(true);
    }

    /**
     * Lazy loads a Crawler object based on the Response;
     * @return Crawler
     */
    public function getCrawler()
    {
        if (!$this->crawler instanceof Crawler) {
            $this->crawler = new Crawler('', $this->getUri()->toString());
            $this->crawler->addContent(
                $this->getResponse()->getBody(true),
                $this->getResponse()->getHeader('Content-Type', true)
            );
        }
        return $this->crawler;
    }

    /**
     * @return DiscoveredUri
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function __sleep()
    {
        /*
         * Because the Crawler isn't serialized correctly, we exclude it from serialization
         * It will be available again after wakeup through lazy loading with getCrawler()
         */
        return array(
            'uri',
            'response',
            'body'
        );
    }

    /**
     * We need to set the body again after deserialization because it was a stream that didn't get serialized
     */
    public function __wakeup()
    {
        $this->response->setBody($this->body);
    }
}

<?php
namespace VDB\Spider;

use Guzzle\Http\Message\Response;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos
 * @copyright 2013 Matthijs van den Bos
 */
class Resource implements Filterable
{
    /** @var bool if the link should be skipped */
    private $isFiltered = false;

    /** @var string */
    private $filterReason = '';

    /** @var UriInterface */
    protected $uri;

    /** @var Response */
    protected $response;

    /** @var Crawler */
    protected $crawler;

    /** @var string */
    protected $body;

    /** @var int */
    public $depthFound;

    /**
     * @param UriInterface $uri
     * @param Response $response
     */
    public function __construct(UriInterface $uri, Response $response)
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
     * @return UriInterface
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

    /**
     * @param bool $filtered
     * @param string $reason
     */
    public function setFiltered($filtered = true, $reason = '')
    {
        $this->isFiltered = $filtered;
        $this->filterReason = $reason;
    }

    /**
     * @return boolean whether the link should be skipped
     */
    public function isFiltered()
    {
        return $this->isFiltered;
    }

    /**
     * @return string
     */
    public function getFilterReason()
    {
        return $this->filterReason;
    }

    /**
     * Get a unique identifier for the filterable item
     * Used for reporting
     *
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getUri()->toString();
    }

    public function __toString()
    {
        return $this->getIdentifier();
    }

    public function __sleep()
    {
        /*
         * Because the Crawler isn't serialized correctly, we exclude it from serialization
         * It will be available again after wakeup through lazy loading with getCrawler()
         */
        return array(
            'isFiltered',
            'filterReason',
            'uri',
            'response',
            'body',
            'depthFound'
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

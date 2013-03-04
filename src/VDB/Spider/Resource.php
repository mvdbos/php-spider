<?php
namespace VDB\Spider;

use VDB\URI\URI;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\BrowserKit\Response;

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

    /** @var URI */
    protected $uri;

    /** @var Response */
    protected $response;

    /** @var Crawler */
    protected $crawler;

    /** @var int */
    public $depthFound;

    /**
     * @param URI $uri
     * @param \Symfony\Component\BrowserKit\Response $response
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     */
    public function __construct(URI $uri, Response $response, Crawler $crawler)
    {
        $this->uri = $uri;
        $this->response = $response;
        $this->crawler = $crawler;
    }

    /**
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    public function getCrawler()
    {
        return $this->crawler;
    }

    /**
     * @return URI
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @return \Symfony\Component\BrowserKit\Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param boolean $filtered
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
        return $this->getUri()->recompose();
    }

    public function __toString()
    {
        return $this->getIdentifier();
    }
}

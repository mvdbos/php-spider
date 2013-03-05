<?php
namespace VDB\Spider\RequestHandler;

use VDB\Spider\RequestHandler\RequestHandler;
use Goutte\Client;
use VDB\URI\URI;
use VDB\Spider\Resource;

use Symfony\Component\DomCrawler\Link;
use Symfony\Component\BrowserKit\Client as AbstractBrowserKitClient;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
class RequestHandlerBrowserKitClient implements RequestHandler
{

    /** @var AbstractBrowserKitClient */
    private $client;


    /**
     * @param AbstractBrowserKitClient $client
     * @return RequestHandler
     */
    public function setClient(AbstractBrowserKitClient $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return AbstractBrowserKitClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param \Symfony\Component\DomCrawler\Link $Link
     * @return Resource
     */
    public function request(URI $uri)
    {
        $crawler = $this->getClient()->request('GET', $uri->toString());
        $response = $this->getClient()->getResponse();
        return new Resource($uri, $response, $crawler);
    }
}

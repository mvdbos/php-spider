<?php
namespace VDB\Spider\RequestHandler;

use Goutte\Client;
use Symfony\Component\BrowserKit\Client as AbstractBrowserKitClient;
use VDB\Spider\RequestHandler\RequestHandler;
use VDB\Spider\Resource;
use VDB\URI\URI;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
class BrowserKitClientRequestHandler implements RequestHandler
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
     * @param URI $uri
     * @return Resource
     */
    public function request(URI $uri)
    {
        $this->getClient()->request('GET', $uri->toString());
        $response = $this->getClient()->getResponse();

        return new Resource($uri, $response);
    }
}

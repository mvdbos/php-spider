<?php
namespace VDB\Spider\RequestHandler;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Message\Request;
use GuzzleHttp\Message\RequestInterface;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
class GuzzleRequestHandler implements RequestHandlerInterface
{
    /** @var Client */
    private $client;

    /**
     * @param Client $client
     * @return RequestHandlerInterface
     */
    public function setClient(Client $client)
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param DiscoveredUri $uri
     * @return Resource
     */
    public function request(DiscoveredUri $uri)
    {
        $response = $this->getClient()->get($uri->toString());
        return new Resource($uri, $response);
    }
}

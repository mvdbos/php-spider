<?php
namespace VDB\Spider\RequestHandler;

use Guzzle\Http\Client;
use Guzzle\Http\Message\Request;
use Guzzle\Http\Message\RequestInterface;
use VDB\Spider\RequestHandler\RequestHandler;
use VDB\Spider\Resource;
use VDB\Uri\UriInterface;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */
class GuzzleRequestHandler implements RequestHandler
{
    /** @var Client */
    private $client;

    /**
     * @param Client $client
     * @return RequestHandler
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
     * @param UriInterface $uri
     * @return Resource
     */
    public function request(UriInterface $uri)
    {
        $response = $this->getClient()->createRequest(RequestInterface::GET, $uri->toString())->send();
        return new Resource($uri, $response);
    }
}

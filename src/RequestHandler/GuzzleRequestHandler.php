<?php

namespace VDB\Spider\RequestHandler;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2021 Matthijs van den Bos <matthijs@vandenbos.org>
 */
class GuzzleRequestHandler implements RequestHandlerInterface
{
    /** @var Client */
    private $client;

    /**
     * @param Client $client
     * @return RequestHandlerInterface
     */
    public function setClient(Client $client): RequestHandlerInterface
    {
        $this->client = $client;

        return $this;
    }

    /**
     * @return Client
     */
    public function getClient(): Client
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * @param DiscoveredUri $uri
     * @return Resource
     * @throws GuzzleException
     * @suppress PhanTypeInvalidThrowsIsInterface
     */
    public function request(DiscoveredUri $uri): Resource
    {
        $response = $this->getClient()->get($uri->toString());
        return new Resource($uri, $response);
    }
}

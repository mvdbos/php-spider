<?php
/**
 * @author Matthijs van den Bos <matthijs@vandenbos.org>
 * @copyright 2013 Matthijs van den Bos
 */

namespace Example;

use VDB\Spider\RequestHandler\GuzzleRequestHandler;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

class LinkCheckRequestHandler extends GuzzleRequestHandler
{
    public function request(DiscoveredUri $uri)
    {
        $response = $this->getClient()->get($uri->toString(), ['http_errors' => false]);
        return new Resource($uri, $response);
    }
}

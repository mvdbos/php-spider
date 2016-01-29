<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Discoverer;

use DOMDocument;
use DOMElement;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Uri\Uri;

abstract class DiscovererTestCase extends TestCase
{
    /** @var \DomDocument */
    protected $domDocument;

    /** @var \DomElement */
    protected $domAnchor;

    /** @var Resource */
    protected $spiderResource;

    /** @var DiscoveredUri */
    protected $uri;

    protected $uriInBody1;

    protected function setUp()
    {
        $this->uriInBody1 = 'http://php-spider.org/contact/';

        // Setup DOM
        $this->domDocument = new \DOMDocument('1', 'UTF-8');

        $html = $this->domDocument->createElement('html');
        $this->domAnchor = $this->domDocument->createElement('a', 'fake');
        $this->domAnchor->setAttribute('href', $this->uriInBody1);

        $this->domDocument->appendChild($html);
        $html->appendChild($this->domAnchor);

        $this->uri = new DiscoveredUri('http://php-spider.org/');

        // Setup Spider\Resource
        $content = $this->domDocument->saveHTML();

        $this->spiderResource = new Resource(
            $this->uri,
            new Response(200, [], $content)
        );
    }

    protected function executeDiscoverer(DiscovererInterface $discoverer)
    {
        $uris = $discoverer->discover($this->spiderResource);
        $uri = $uris[0];

        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $uri);
        $this->assertEquals($this->uriInBody1, $uri->toString());
    }
}

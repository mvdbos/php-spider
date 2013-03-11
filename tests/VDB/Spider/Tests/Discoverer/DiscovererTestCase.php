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
use Guzzle\Http\Message\Response;
use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Uri;

abstract class DiscovererTestCase extends TestCase
{
    /** @var DomDocument */
    protected $domDocument;

    /** @var DomElement */
    protected $domAnchor;

    /** @var Spider */
    protected $spider;

    /** @var Resource */
    protected $spiderResource;

    /** @var Uri */
    protected $uri;

    protected function setUp()
    {
        // Setup DOM
        $this->domDocument = new \DOMDocument('1', 'UTF-8');

        $html = $this->domDocument->createElement('html');
        $this->domAnchor = $this->domDocument->createElement('a', 'fake');
        $this->domAnchor->setAttribute('href', 'http://php-spider.org/contact/');

        $this->domDocument->appendChild($html);
        $html->appendChild($this->domAnchor);

        $this->uri = new Uri($this->domAnchor->getAttribute('href'));

        $this->spider = new Spider('http://php-spider.org');

        // Setup Spider\Resource
        $content = $this->domDocument->saveHTML();

        $this->spiderResource = new Resource(
            $this->uri,
            new Response(200, null, $content)
        );
    }
}

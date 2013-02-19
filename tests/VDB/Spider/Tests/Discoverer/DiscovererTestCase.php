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

use Symfony\Component\DomCrawler\Crawler;
use Pimple;
use VDB\Spider\Tests\TestCase;
use Symfony\Component\BrowserKit\Response;
use DOMDocument;
use DOMElement;
use VDB\Spider\Document;
use VDB\URI\GenericURI;
use VDB\Spider\Spider;

abstract class DiscovererTestCase extends TestCase
{
    /** @var DomDocument */
    protected $domDocument;

    /** @var DomElement */
    protected $domAnchor;

    /** @var Spider */
    protected $spider;

    /** @var Document */
    protected $spiderDocument;

    /** @var GenericURI */
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

        $this->uri = new GenericURI($this->domAnchor->getAttribute('href'));

        $this->spider = new Spider(new Pimple());

        // Setup Spider\Document
        $crawler = new Crawler($this->domDocument, 'http://php-spider.org');
        $this->spiderDocument = new Document(
            $this->uri,
            new Response(200),
            $crawler
        );
    }
}

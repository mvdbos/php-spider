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
use DomElement;
use ErrorException;
use Exception;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Discoverer\DiscovererInterface;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Uri\Exception\UriSyntaxException;

abstract class DiscovererTestCase extends TestCase
{
    protected DomDocument $domDocument;
    protected DomElement $domAnchor;
    protected DomElement $domAnchor2;
    protected Resource $spiderResource;
    protected DiscoveredUri $uri;
    protected string $uriInBody1;
    protected string $uriInBody2;
    protected string $resourceContent;

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->uriInBody1 = 'http://php-spider.org/contact/';
        $this->uriInBody2 = 'http://php-spider.org:8080/internal/';

        $this->uri = new DiscoveredUri('http://php-spider.org/', 0);

        $this->spiderResource = self::createResourceWithLinks(
            $this->uri,
            [$this->uriInBody1, $this->uriInBody2]
        );
    }

    protected function executeDiscoverer(DiscovererInterface $discoverer)
    {
        $uris = $discoverer->discover($this->spiderResource);
        $uri = $uris[0];

        $this->assertInstanceOf('VDB\\Spider\\Uri\\DiscoveredUri', $uri);
        $this->assertEquals($this->uriInBody1, $uri->toString());
    }
    /**
     * @param string[] $uris
     * @return false|string
     * @throws Exception
     */
    public static function createDocumentWithLinks(array $uris): string
    {
        $domDocument = new DOMDocument('1', 'UTF-8');
        $html = $domDocument->createElement('html');
        $domDocument->appendChild($html);

        foreach ($uris as $i => $href) {
            $domAnchor = $domDocument->createElement('a', 'fake' . $i);
            $domAnchor->setAttribute('href', $href);
            $html->appendChild($domAnchor);
        }
        $doc = $domDocument->saveHTML();
        if (!$doc) {
            throw new Exception("Could not create DOM document");
        }
        return $doc;
    }

    /**
     * @param DiscoveredUri $resourceUri
     * @param string[] $uris
     * @return Resource
     * @throws Exception
     */
    public static function createResourceWithLinks(
        DiscoveredUri $resourceUri,
        array $uris
    ): Resource {
        $resourceContent = self::createDocumentWithLinks($uris);

        return new Resource(
            $resourceUri,
            new Response(200, [], $resourceContent)
        );
    }
}

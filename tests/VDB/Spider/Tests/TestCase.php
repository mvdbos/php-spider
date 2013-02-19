<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests;

use VDB\URI\GenericURI;
use Symfony\Component\BrowserKit\Response;
use Symfony\Component\DomCrawler\Crawler;
use VDB\Spider\Document;

/**
 *
 */
class TestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @param GenericURI $uri
     * @param \Symfony\Component\DomCrawler\Crawler $crawler
     * @return \VDB\Spider\Document
     */
    protected function getDocument(GenericURI $uri, Crawler $crawler = null)
    {
        if (is_null($crawler)) {
            $crawler = new Crawler(null, $uri->recompose());
        }
        return new Document($uri, new Response(), $crawler);
    }
}

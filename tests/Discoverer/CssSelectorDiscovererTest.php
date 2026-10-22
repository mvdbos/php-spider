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

use PHPUnit\Framework\Attributes\CoversClass;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Discoverer\CssSelectorDiscoverer;
use VDB\Spider\Resource;
use VDB\Spider\Uri\DiscoveredUri;

#[CoversClass(\VDB\Spider\Discoverer\CrawlerDiscoverer::class)]
#[CoversClass(\VDB\Spider\Discoverer\CssSelectorDiscoverer::class)]
class CssSelectorDiscovererTest extends DiscovererTestCase
{
    public function testDiscover()
    {
        $discoverer = new CssSelectorDiscoverer("a");
        $this->executeDiscoverer($discoverer);
    }

    public function testDiscoverIgnoresInvalidUris()
    {
        $resource = new Resource(
            new DiscoveredUri('http://php-spider.org/', 0),
            new Response(200, [], '<html><body><a href="http://[invalid">Bad</a><a href="/ok">Ok</a></body></html>')
        );

        $uris = (new CssSelectorDiscoverer("a"))->discover($resource);

        $this->assertCount(1, $uris);
        $this->assertEquals('http://php-spider.org/ok', $uris[0]->toString());
    }
}

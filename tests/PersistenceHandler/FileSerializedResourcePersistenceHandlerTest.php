<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\PersistenceHandler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use ErrorException;
use GuzzleHttp\Psr7\Response;
use VDB\Spider\Downloader\Downloader;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\Resource;
use VDB\Spider\Spider;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;
use VDB\Spider\RequestHandler\RequestHandlerInterface;
use VDB\Uri\Exception\UriSyntaxException;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
#[CoversClass(\VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler::class)]
#[CoversClass(\VDB\Spider\PersistenceHandler\FilePersistenceHandler::class)]
class FileSerializedResourcePersistenceHandlerTest extends TestCase
{
    /**
     * @var FileSerializedResourcePersistenceHandler
     */
    protected FileSerializedResourcePersistenceHandler $handler;

    protected ?string $persistenceRootPath = null;


    public function setUp(): void
    {
        $this->persistenceRootPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spider-UT' . DIRECTORY_SEPARATOR;
        exec('rm -rf ' . $this->persistenceRootPath);

        $this->handler = new FileSerializedResourcePersistenceHandler(sys_get_temp_dir());
        $this->handler->setSpiderId('spider-UT');
    }

    /**
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testPathExtension()
    {
        $resource1 = new Resource(
            new DiscoveredUri("http://example.com", 0),
            new Response(200, [], "Test Body Contents 1")
        );

        $this->assertEquals('', $resource1->getUri()->getPath());

        $this->handler->persist($resource1);

        $this->assertEquals(1, $this->handler->count());
        /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
        foreach ($this->handler as $path => $resource) {
            $this->assertStringEndsWith('/index.html', $path);
        }
    }

    /**
     * The count is cached after the first call (which scans the directory) and kept in
     * sync by subsequent persists, so the crawl loop does not rescan the directory tree
     * on every download limit check.
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testCountIsCachedAndKeptInSyncByPersist()
    {
        $this->assertEquals(0, $this->handler->count());

        $this->handler->persist(
            new Resource(new DiscoveredUri("http://example.com/page1", 0), new Response(200, [], "Body 1"))
        );
        $this->assertEquals(1, $this->handler->count());

        $this->handler->persist(
            new Resource(new DiscoveredUri("http://example.com/page2", 0), new Response(200, [], "Body 2"))
        );
        $this->assertEquals(2, $this->handler->count());
    }

    public function testOverwritingFileDoesNotIncreaseStoredEntryCount(): void
    {
        $this->assertSame(0, $this->handler->count());

        $uri = 'http://example.com/same.html';
        $this->handler->persist(new Resource(new DiscoveredUri($uri, 0), new Response(200, [], 'First')));
        $this->assertSame(1, $this->handler->count());

        // Query and scheme do not participate in this handler's existing file identity.
        $this->handler->persist(
            new Resource(new DiscoveredUri('https://example.com/same.html?revision=2', 0), new Response(200, [], 'Replacement'))
        );
        $this->assertSame(1, $this->handler->count());
    }

    public function testCountingAfterRepeatedWritesCountsStoredFiles(): void
    {
        $this->handler->persist(
            new Resource(new DiscoveredUri('http://example.com/same.html', 0), new Response(200, [], 'First'))
        );
        $this->handler->persist(
            new Resource(new DiscoveredUri('http://example.com/same.html', 0), new Response(200, [], 'Replacement'))
        );

        $this->assertSame(1, $this->handler->count());
    }

    public function testCountIncludesFilesAlreadyInSelectedNamespace(): void
    {
        $existingFile = $this->persistenceRootPath . 'example.com/existing.html';
        mkdir(dirname($existingFile), 0777, true);
        file_put_contents($existingFile, 'Existing');

        $this->assertSame(1, $this->handler->count());
    }

    public function testRewindingAfterCompletedWritesIteratesCurrentFiles(): void
    {
        $this->handler->persist(
            new Resource(new DiscoveredUri('http://example.com/first.html', 0), new Response(200, [], 'First'))
        );
        $this->assertCount(1, iterator_to_array($this->handler));

        $this->handler->persist(
            new Resource(new DiscoveredUri('http://example.com/second.html', 0), new Response(200, [], 'Second'))
        );

        $this->assertCount(2, iterator_to_array($this->handler));
    }

    public function testExistingFileAtDownloadLimitStopsSpiderBeforeFetching(): void
    {
        $this->handler->persist(
            new Resource(new DiscoveredUri('http://example.com/page.html', 0), new Response(200, [], 'Cached'))
        );

        $requestHandler = $this->createMock(RequestHandlerInterface::class);
        $requestHandler->expects($this->never())->method('request');
        $downloader = new Downloader($this->handler, $requestHandler, [], 1);
        $spider = new Spider('http://example.com/page.html', null, null, $downloader, 'spider-UT');

        $spider->crawl();

        $this->assertSame(1, $this->handler->count());
    }

    #[DataProvider('persistenceProvider')]
    public function testPersist(string $uriString)
    {
        [$resource, $expectedFilePath, $expectedFileContents] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            $uriString
        );
        $this->handler->persist($resource);

        $this->assertFileExists($expectedFilePath);

        $this->assertEquals(1, $this->handler->count());
        // Check the file contents through iterator access and directly
        foreach ($this->handler as $path => $resource) {
            $savedResource = unserialize(file_get_contents($path));
            $this->assertEquals(
                $expectedFileContents,
                $savedResource->getResponse()->getBody()
            );

            $this->assertEquals(
                $expectedFileContents,
                $resource->getResponse()->getBody()
            );
        }
    }

    #[DataProvider('persistenceWithoutFilenameProvider')]
    public function testPersistResourcesWithoutFilename(string $uriString)
    {
        [$resource, $expectedFilePath, $expectedFileContents] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            $uriString
        );
        $this->handler->persist($resource);

        $this->assertFileExists($expectedFilePath);

        $savedResource = unserialize(file_get_contents($expectedFilePath));
        $this->assertEquals(
            $expectedFileContents,
            $savedResource->getResponse()->getBody()
        );
    }

    /**
     * @return array
     */
    public static function persistenceWithoutFilenameProvider(): array
    {
        $data = [];

        $data[] = ['http://example.org/domains/Internet/'];

        $data[] = ['http://example.org/domains/Internet/Abuse/'];

        return $data;
    }

    /**
     * @throws UriSyntaxException
     * @throws ErrorException
     */
    protected function buildPersistenceProviderRecord($fixturePath, $uriString): array
    {
        $resource = $this->buildResourceFromFixture(
            $fixturePath,
            $uriString
        );
        $expectedFileContents = $this->getFixtureContent(__DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html');
        $expectedFilePath = $this->buildExpectedFilePath($uriString);

        return [$resource, $expectedFilePath, $expectedFileContents];
    }

    protected function buildExpectedFilePath($uriString): string
    {
        $expectedFilePath = $this->persistenceRootPath . parse_url($uriString)['host'] . parse_url($uriString)['path'];
        if (substr($expectedFilePath, -1, 1) === '/') {
            $expectedFilePath .= 'index.html';
        }

        return $expectedFilePath;
    }

    /**
     * @return array
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public static function persistenceProvider(): array
    {
        $data = [];

        $data[] = ['http://example.org/domains/special/test1.html'];

        $data[] = ['http://example.org/domains/special/test2.html'];

        $data[] = ['http://example.org/domains/special/subdir/test3.html'];

        return $data;
    }
}

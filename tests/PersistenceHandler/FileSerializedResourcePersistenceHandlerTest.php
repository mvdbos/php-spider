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
use GuzzleHttp\Psr7\Response;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\Resource;
use VDB\Spider\Tests\TestCase;
use VDB\Spider\Uri\DiscoveredUri;

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

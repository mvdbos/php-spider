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

use ErrorException;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;
use VDB\Spider\Tests\Helpers\ResourceBuilder;
use VDB\Spider\Tests\TestCase;
use VDB\Uri\Exception\UriSyntaxException;

/**
 * @SuppressWarnings(PHPMD.LongClassName)
 */
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
     * @covers       \VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler
     * @covers       \VDB\Spider\PersistenceHandler\FilePersistenceHandler
     *
     * @throws ErrorException
     * @throws UriSyntaxException
     */
    public function testPathExtension()
    {
        $resource1 = ResourceBuilder::create()
            ->withUri('http://example.com')
            ->withBody('Test Body Contents 1')
            ->build();

        $this->assertEquals('', $resource1->getUri()->getPath());

        $this->handler->persist($resource1);

        $this->assertEquals(1, $this->handler->count());
        /** @SuppressWarnings(PHPMD.UnusedLocalVariable) */
        foreach ($this->handler as $path => $resource) {
            $this->assertStringEndsWith('/index.html', $path);
        }
    }

    /**
     * @covers       \VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler
     * @covers       \VDB\Spider\PersistenceHandler\FilePersistenceHandler
     *
     * @dataProvider persistenceProvider
     */
    public function testPersist($resource, $expectedFilePath, $expectedFileContents)
    {
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

    /**
     * @covers       \VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler
     * @covers       \VDB\Spider\PersistenceHandler\FilePersistenceHandler
     *
     * @dataProvider persistenceWithoutFilenameProvider
     */
    public function testPersistResourcesWithoutFilename($resource, $expectedFilePath, $expectedFileContents)
    {
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
    public function persistenceWithoutFilenameProvider(): array
    {
        // This must be set here instead of in setup methods, because providers
        // get executed first
        if (is_null($this->persistenceRootPath)) {
            $this->persistenceRootPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spider-UT' . DIRECTORY_SEPARATOR;
        }

        $data = [];

        $data[] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            'http://example.org/domains/Internet/'
        );

        $data[] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            'http://example.org/domains/Internet/Abuse/'
        );

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
    public function persistenceProvider(): array
    {
        // This must be set here instead of in setup methods, because providers
        // get executed first
        if (is_null($this->persistenceRootPath)) {
            $this->persistenceRootPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spider-UT' . DIRECTORY_SEPARATOR;
        }

        $data = [];

        $data[] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            'http://example.org/domains/special/test1.html'
        );

        $data[] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            'http://example.org/domains/special/test2.html'
        );

        $data[] = $this->buildPersistenceProviderRecord(
            __DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html',
            'http://example.org/domains/special/subdir/test3.html'
        );

        return $data;
    }
}

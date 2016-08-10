<?php

/*
 * This file is part of the Spider package.
 *
 * (c) Matthijs van den Bos <matthijs@vandenbos.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VDB\Spider\Tests\Downloader;

use VDB\Spider\Tests\TestCase;
use VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler;

/**
 *
 */
class FileSerializedResourcePersistenceHandlerTest extends TestCase
{
    /**
     * @var FileSerializedResourcePersistenceHandler
     */
    protected $handler;

    protected $persistenceRootPath;


    public function setUp()
    {
        $this->persistenceRootPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'spider-UT' . DIRECTORY_SEPARATOR;
        exec('rm -rf ' . $this->persistenceRootPath);

        $this->handler = new FileSerializedResourcePersistenceHandler(sys_get_temp_dir());
        $this->handler->setSpiderId('spider-UT');
    }

    /**
     * @covers VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler
     * @covers VDB\Spider\PersistenceHandler\FilePersistenceHandler
     *
     * @dataProvider persistenceProvider
     */
    public function testPersist($resource, $expectedFilePath, $expectedFileContents)
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
     * @covers VDB\Spider\PersistenceHandler\FileSerializedResourcePersistenceHandler
     * @covers VDB\Spider\PersistenceHandler\FilePersistenceHandler
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

    public function persistenceWithoutFilenameProvider()
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

    public function persistenceProvider()
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

    protected function buildPersistenceProviderRecord($fixturePath, $uriString)
    {
        $resource = $this->buildResourceFromFixture(
            $fixturePath,
            $uriString
        );
        $expectedFileContents = $this->getFixtureContent(__DIR__ . '/../Fixtures/DownloaderTestHTMLResource.html');
        $expectedFilePath = $this->buildExpectedFilePath($uriString);

        return [$resource, $expectedFilePath, $expectedFileContents];
    }

    protected function buildExpectedFilePath($uriString)
    {
        $expectedFilePath = $this->persistenceRootPath . parse_url($uriString)['host'] . parse_url($uriString)['path'];
        if (substr($expectedFilePath, -1, 1) === '/') {
            $expectedFilePath .= 'index.html';
        }

        return $expectedFilePath;
    }
}

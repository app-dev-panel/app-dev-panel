<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;

final class FileStorageTest extends AbstractStorageTestCase
{
    private string $path = __DIR__ . '/runtime';

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->path);
    }

    public function testDefaultHistorySizeConstant(): void
    {
        $this->assertSame(50, FileStorage::DEFAULT_HISTORY_SIZE);
    }

    #[DataProvider('dataProvider')]
    public function testFlushWithGC(array $data): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $storage->setHistorySize(5);
        $collector = $this->createFakeCollector($data);

        $storage->addCollector($collector);
        $storage->flush();
        $this->assertLessThanOrEqual(5, count($storage->read(StorageInterface::TYPE_SUMMARY, null)));
    }

    #[DataProvider('dataProvider')]
    public function testHistorySize(array $data): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $idGenerator->reset();
        $storage = $this->getStorage($idGenerator);
        $storage->setHistorySize(2);
        $collector = $this->createFakeCollector($data);

        $storage->addCollector($collector);
        $storage->flush();
        $idGenerator->reset();

        $storage->addCollector($collector);
        $storage->flush();
        $idGenerator->reset();

        $storage->addCollector($collector);
        $storage->flush();
        $idGenerator->reset();

        $read = $storage->read(StorageInterface::TYPE_SUMMARY, null);
        $this->assertCount(2, $read);
    }

    #[DataProvider('dataProvider')]
    public function testClear(array $data): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector($data);

        $storage->addCollector($collector);
        $storage->flush();
        $storage->clear();
        $this->assertDirectoryDoesNotExist($this->path);
    }

    public function testSummaryIsPlainJsonAndDataObjectsAreGzipped(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector([1, 2, 3]);

        $storage->addCollector($collector);
        $storage->flush();

        // Summary is plain .json
        $summaryFiles = glob($this->path . '/**/**/summary.json') ?: [];
        $this->assertCount(1, $summaryFiles, 'Expected 1 summary.json file');
        $summaryContent = file_get_contents($summaryFiles[0]);
        $this->assertNotFalse($summaryContent);
        $this->assertJson($summaryContent);

        $summaryGzFiles = glob($this->path . '/**/**/summary.json.gz') ?: [];
        $this->assertCount(0, $summaryGzFiles, 'No summary.json.gz should exist');

        // Data and objects are .json.gz
        $dataGzFiles = glob($this->path . '/**/**/data.json.gz') ?: [];
        $this->assertCount(1, $dataGzFiles, 'Expected 1 data.json.gz file');

        $objectsGzFiles = glob($this->path . '/**/**/objects.json.gz') ?: [];
        $this->assertCount(1, $objectsGzFiles, 'Expected 1 objects.json.gz file');

        // Verify gzip files are valid
        foreach ([...$dataGzFiles, ...$objectsGzFiles] as $file) {
            $contents = file_get_contents($file);
            $this->assertNotFalse($contents);
            $decoded = gzdecode($contents);
            $this->assertNotFalse($decoded);
            $this->assertJson($decoded);
        }
    }

    public function testReadLegacyGzipFiles(): void
    {
        $id = 'legacy-gz-entry';
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';
        mkdir($basePath, 0777, true);

        // Write gzip files (legacy format)
        file_put_contents($basePath . 'summary.json.gz', gzencode(json_encode(['id' => $id, 'collectors' => []])));
        file_put_contents($basePath . 'data.json.gz', gzencode(json_encode(['test' => 'value'])));
        file_put_contents($basePath . 'objects.json.gz', gzencode(json_encode([])));

        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertCount(1, $summaries);
        $this->assertSame($id, $summaries[$id]['id']);
    }

    public function testWriteViaWriteMethodProducesCorrectFormats(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('test-id', ['id' => 'test-id'], ['key' => 'value'], []);

        // Summary is plain .json
        $this->assertFileExists($this->path . '/' . date('Y-m-d') . '/test-id/summary.json');
        $this->assertFileDoesNotExist($this->path . '/' . date('Y-m-d') . '/test-id/summary.json.gz');

        // Data and objects are .json.gz
        $gzFiles = glob($this->path . '/**/test-id/*.json.gz');
        $this->assertCount(2, $gzFiles, 'Expected 2 .json.gz files (data, objects)');
    }

    public function testReadEmptyDirectory(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // No data written yet
        $result = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertSame([], $result);
    }

    public function testReadNonExistentId(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $result = $storage->read(StorageInterface::TYPE_DATA, 'non-existent-id');
        $this->assertSame([], $result);
    }

    public function testReadByIdReturnsSpecificEntry(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('test-id-1', ['id' => 'test-id-1'], ['key' => 'value1'], []);
        $storage->write('test-id-2', ['id' => 'test-id-2'], ['key' => 'value2'], []);

        $result = $storage->read(StorageInterface::TYPE_SUMMARY, 'test-id-1');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('test-id-1', $result);
        $this->assertSame('test-id-1', $result['test-id-1']['id']);
    }

    public function testFlushCollectsSummaryData(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $summaryCollector = $this->createFakeSummaryCollector(['test' => 'data']);
        $storage->addCollector($summaryCollector);
        $storage->flush();

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY, $idGenerator->getId());
        $this->assertCount(1, $summaries);
        $summary = $summaries[$idGenerator->getId()];
        $this->assertSame($idGenerator->getId(), $summary['id']);
        $this->assertArrayHasKey('collectors', $summary);
    }

    public function testJsonAndLegacyGzipFilesCoexist(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Write a plain JSON entry
        $storage->write('json-entry', ['id' => 'json-entry'], ['data' => 'json'], []);

        // Write a legacy gzip entry manually
        $legacyDir = $this->path . '/' . date('Y-m-d') . '/legacy-gz-entry/';
        mkdir($legacyDir, 0777, true);
        file_put_contents($legacyDir . 'summary.json.gz', gzencode(json_encode(['id' => 'legacy-gz-entry'])));

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertCount(2, $summaries);
        $this->assertArrayHasKey('json-entry', $summaries);
        $this->assertArrayHasKey('legacy-gz-entry', $summaries);
    }

    public function testReadAllSortsChronologically(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Write entries and manipulate timestamps
        $storage->write('entry-a', ['id' => 'entry-a'], [], []);
        $storage->write('entry-b', ['id' => 'entry-b'], [], []);

        // Touch entry-a to be newer
        $jsonFiles = glob($this->path . '/**/entry-a/summary.json');
        if ($jsonFiles) {
            touch($jsonFiles[0], time() + 10);
        }

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $ids = array_keys($summaries);
        // entry-b should come first (older), entry-a last (newer)
        $this->assertSame('entry-b', $ids[0]);
        $this->assertSame('entry-a', $ids[1]);
    }

    public function testWriteAndReadObjects(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('obj-test', ['id' => 'obj-test'], [], ['SomeClass#1' => ['prop' => 'value']]);

        $result = $storage->read(StorageInterface::TYPE_OBJECTS, 'obj-test');
        $this->assertArrayHasKey('obj-test', $result);
    }

    public function testClearNonExistentDirectory(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Clearing when directory doesn't exist should not throw
        $storage->clear();
        $this->assertDirectoryDoesNotExist($this->path);
    }

    public function testFlushClearsCollectors(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector(['key' => 'value']);

        $storage->addCollector($collector);
        $this->assertNotEmpty($storage->getData());

        $storage->flush();
        $this->assertEmpty($storage->getData());
    }

    public function testWriteAndReadDataType(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $data = ['collector1' => ['items' => [1, 2, 3]]];
        $storage->write('data-test-id', ['id' => 'data-test-id'], $data, []);

        $result = $storage->read(StorageInterface::TYPE_DATA, 'data-test-id');
        $this->assertArrayHasKey('data-test-id', $result);
    }

    public function testReadByIdReturnsEmptyForNonExistentType(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('some-id', ['id' => 'some-id'], [], []);

        // Try reading objects for an ID that has no objects file
        // Actually objects are always written, so read a completely non-existent id
        $result = $storage->read(StorageInterface::TYPE_DATA, 'completely-nonexistent');
        $this->assertSame([], $result);
    }

    public function testMultipleCollectors(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $collector1 = $this->createFakeCollector(['data1']);
        $collector2 = $this->createFakeSummaryCollector(['data2']);

        $storage->addCollector($collector1);
        $storage->addCollector($collector2);

        $data = $storage->getData();
        $this->assertCount(2, $data);
        $this->assertArrayHasKey('Mock_Collector', $data);
        $this->assertArrayHasKey('SummaryMock_Collector', $data);
    }

    public function testSetHistorySize(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $storage->setHistorySize(1);

        $collector = $this->createFakeCollector([1]);

        $storage->addCollector($collector);
        $storage->flush();
        $idGenerator->reset();

        $storage->addCollector($collector);
        $storage->flush();
        $idGenerator->reset();

        $storage->addCollector($collector);
        $storage->flush();

        $read = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertLessThanOrEqual(1, count($read));
    }

    public function testDefaultCompressionLevelConstant(): void
    {
        $this->assertSame(1, FileStorage::DEFAULT_COMPRESSION_LEVEL);
    }

    public function testCustomCompressionLevel(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new FileStorage(new Aliases()->get($this->path), $idGenerator, compressionLevel: 9);

        $storage->write('compress-test', ['id' => 'compress-test'], ['key' => 'value'], []);

        $result = $storage->read(StorageInterface::TYPE_SUMMARY, 'compress-test');
        $this->assertArrayHasKey('compress-test', $result);
    }

    public function testExcludedClassesPassedToStorage(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new FileStorage(
            new Aliases()->get($this->path),
            $idGenerator,
            excludedClasses: ['SomeExcludedClass'],
        );

        $collector = $this->createFakeCollector(['test']);
        $storage->addCollector($collector);
        $storage->flush();

        // Should flush without errors even with excluded classes
        $result = $storage->read(StorageInterface::TYPE_DATA, $idGenerator->getId());
        $this->assertNotEmpty($result);
    }

    public function testGzipPreferredWhenBothFormatsExist(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $id = 'dual-format-entry';
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';
        mkdir($basePath, 0o777, true);

        // Write both plain JSON and gzip for the same entry
        file_put_contents($basePath . 'summary.json', json_encode(['id' => $id, 'format' => 'json']));
        file_put_contents($basePath . 'summary.json.gz', gzencode(json_encode(['id' => $id, 'format' => 'gz'])));

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertCount(1, $summaries);
        // When both exist in readAll, gz takes priority (dedup skips json)
        $this->assertSame('gz', $summaries[$id]['format']);
    }

    public function testReadEntryByIdWithLegacyGzipFile(): void
    {
        $id = 'legacy-by-id';
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';
        mkdir($basePath, 0o777, true);

        // Write gzip files only (legacy format)
        file_put_contents($basePath . 'data.json.gz', gzencode(json_encode(['key' => 'val'])));

        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Read by specific ID — exercises readFile() .json.gz fallback path
        $result = $storage->read(StorageInterface::TYPE_DATA, $id);
        $this->assertArrayHasKey($id, $result);
        $this->assertSame(['key' => 'val'], $result[$id]);
    }

    public function testReadEntryByIdMissingTypeFileReturnsEmpty(): void
    {
        $id = 'partial-entry';
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';
        mkdir($basePath, 0o777, true);

        // Only write summary, not data
        file_put_contents($basePath . 'summary.json', json_encode(['id' => $id]));

        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Try to read data type — readFile returns null
        $result = $storage->read(StorageInterface::TYPE_DATA, $id);
        $this->assertSame([], $result);
    }

    public function getStorage(DebuggerIdGenerator $idGenerator): FileStorage
    {
        return new FileStorage(new Aliases()->get($this->path), $idGenerator);
    }
}

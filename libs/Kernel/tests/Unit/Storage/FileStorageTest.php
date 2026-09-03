<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageIdValidator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\Kernel\Tests\Support\Stub\ResourceHoldingStreamWrapper;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
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

    /**
     * Regression for GitHub issue #114: `JsonException "Type is not supported"`
     * thrown from `Debugger::shutdown()` on asset-serving requests.
     *
     * Collector data holding raw resources (including one opened through a
     * user-space stream wrapper, whose meta-data embeds the wrapper object) and
     * a PSR-7 response backed by a file stream must flush to valid JSON.
     */
    public function testFlushWithResourcesAndStreamBackedResponseProducesValidJson(): void
    {
        ResourceHoldingStreamWrapper::register();
        $memory = fopen('php://memory', 'r+');
        $wrapped = fopen(ResourceHoldingStreamWrapper::SCHEME . '://asset.png', 'r');
        $file = tmpfile();
        fwrite($file, str_repeat("\x89PNG\r\n", 16));
        rewind($file);

        try {
            $response = new Response(200, ['Content-Type' => 'image/png'], Utils::streamFor($file));
            $data = [
                'memory' => $memory,
                'wrapped' => $wrapped,
                'response' => $response,
                'closure' => static fn(): string => 'x',
                'nested' => ['deep' => ['resource' => $memory]],
            ];

            $idGenerator = new DebuggerIdGenerator();
            $storage = $this->getStorage($idGenerator);
            $storage->addCollector($this->createFakeCollector($data));
            $storage->addCollector($this->createFakeSummaryCollector($data));

            $storage->flush();

            $entryDir = $this->path . '/' . date('Y-m-d') . '/' . $idGenerator->getId();
            foreach ([StorageInterface::TYPE_DATA, StorageInterface::TYPE_OBJECTS] as $type) {
                $raw = gzdecode((string) file_get_contents($entryDir . '/' . $type . '.json.gz'));
                $decoded = json_decode((string) $raw, true, 512, JSON_THROW_ON_ERROR);
                $this->assertIsArray($decoded, $type . ' must decode to an array');
            }
            $summary = json_decode(
                (string) file_get_contents($entryDir . '/' . StorageInterface::TYPE_SUMMARY . '.json'),
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
            $this->assertSame($idGenerator->getId(), $summary['id']);

            $stored = $storage->read(StorageInterface::TYPE_DATA, $idGenerator->getId())[$idGenerator->getId()];
            $collected = $stored['Mock_Collector'];
            $this->assertSame('user-space', $collected['wrapped']['wrapper_type']);
            $this->assertIsString($collected['wrapped']['wrapper_data']);
            $this->assertSame('PHP', $collected['memory']['wrapper_type']);
            // Closures dump as {"Closure#<id>": {__closure: true, ...}}
            $this->assertArrayHasKey('__closure', (array) current($collected['closure']));
            // Nested objects are collapsed to references in data.json; the full dump lives in objects.json.
            $this->assertStringStartsWith('object@GuzzleHttp\Psr7\Response#', $collected['response']);
            $objects = $storage->read(StorageInterface::TYPE_OBJECTS, $idGenerator->getId())[$idGenerator->getId()];
            $this->assertStringContainsString('image/png', json_encode(
                $objects,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            ));
        } finally {
            fclose($memory);
            fclose($wrapped);
            ResourceHoldingStreamWrapper::unregister();
        }
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidIds(): iterable
    {
        yield 'traversal' => ['../../etc'];
        yield 'nested traversal' => ['../../etc/passwd'];
        yield 'slash' => ['foo/bar'];
        yield 'backslash' => ['foo\\bar'];
        yield 'dot' => ['foo.json'];
        yield 'glob' => ['*'];
        yield 'empty' => [''];
        yield 'space' => ['a b'];
        yield 'null byte' => ["abc\0def"];
        yield 'too long' => [str_repeat('x', 65)];
    }

    #[DataProvider('invalidIds')]
    public function testReadRejectsInvalidIdBeforeTouchingTheFilesystem(string $id): void
    {
        $storage = $this->getStorage(new DebuggerIdGenerator());

        try {
            $storage->read(StorageInterface::TYPE_DATA, $id);
            $this->fail('Expected InvalidArgumentException.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid debug entry id', $e->getMessage());
        }

        $this->assertDirectoryDoesNotExist($this->path);
    }

    #[DataProvider('invalidIds')]
    public function testWriteRejectsInvalidIdBeforeTouchingTheFilesystem(string $id): void
    {
        $storage = $this->getStorage(new DebuggerIdGenerator());

        try {
            $storage->write($id, ['id' => $id], ['key' => 'value'], []);
            $this->fail('Expected InvalidArgumentException.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('Invalid debug entry id', $e->getMessage());
        }

        $this->assertDirectoryDoesNotExist($this->path);
        $this->assertDirectoryDoesNotExist(dirname($this->path) . '/etc');
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function validIds(): iterable
    {
        yield 'generated' => [new DebuggerIdGenerator()->getId()];
        yield 'dashes and underscores' => ['external-123_abc'];
        yield 'single char' => ['a'];
        yield 'max length' => [str_repeat('x', 64)];
    }

    #[DataProvider('validIds')]
    public function testValidIdsRoundTrip(string $id): void
    {
        $this->assertSame($id, StorageIdValidator::assertValid($id));

        $storage = $this->getStorage(new DebuggerIdGenerator());
        $storage->write($id, ['id' => $id], ['key' => 'value'], []);

        $this->assertSame(['id' => $id], $storage->read(StorageInterface::TYPE_SUMMARY, $id)[$id]);
        $this->assertSame(['key' => 'value'], $storage->read(StorageInterface::TYPE_DATA, $id)[$id]);
    }

    public function testStorageIdValidatorAcceptsOnlyStrings(): void
    {
        $this->assertFalse(StorageIdValidator::isValid(null));
        $this->assertFalse(StorageIdValidator::isValid(123));
        $this->assertFalse(StorageIdValidator::isValid(['a']));
        $this->assertTrue(StorageIdValidator::isValid('abc'));
    }

    public function getStorage(DebuggerIdGenerator $idGenerator): FileStorage
    {
        return new FileStorage(new Aliases()->get($this->path), $idGenerator);
    }
}

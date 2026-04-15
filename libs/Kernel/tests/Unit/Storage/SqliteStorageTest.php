<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\SqliteStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;

#[RequiresPhpExtension('pdo_sqlite')]
final class SqliteStorageTest extends AbstractStorageTestCase
{
    private string $path;

    protected function setUp(): void
    {
        parent::setUp();
        $this->path = sys_get_temp_dir() . '/adp-test-' . uniqid() . '/debug.db';
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (file_exists($this->path)) {
            unlink($this->path);
        }
        $dir = dirname($this->path);
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }

    public function testDefaultHistorySizeConstant(): void
    {
        $this->assertSame(50, SqliteStorage::DEFAULT_HISTORY_SIZE);
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

        $result = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertSame([], $result);
    }

    public function testReadEmptyStorage(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

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

    public function testReadAllSortsChronologically(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Write entries with different timestamps
        $storage->write('entry-b', ['id' => 'entry-b'], [], []);
        sleep(1); // Ensure different created_at
        $storage->write('entry-a', ['id' => 'entry-a'], [], []);

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

    public function testExcludedClassesPassedToStorage(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = new SqliteStorage($this->path, $idGenerator, excludedClasses: ['SomeExcludedClass']);

        $collector = $this->createFakeCollector(['test']);
        $storage->addCollector($collector);
        $storage->flush();

        $result = $storage->read(StorageInterface::TYPE_DATA, $idGenerator->getId());
        $this->assertNotEmpty($result);
    }

    public function testWriteOverwritesExistingEntry(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('entry-1', ['id' => 'entry-1', 'v' => 1], ['d' => 'old'], []);
        $storage->write('entry-1', ['id' => 'entry-1', 'v' => 2], ['d' => 'new'], []);

        $result = $storage->read(StorageInterface::TYPE_DATA, 'entry-1');
        $this->assertSame(['d' => 'new'], $result['entry-1']);

        $summary = $storage->read(StorageInterface::TYPE_SUMMARY, 'entry-1');
        $this->assertSame(2, $summary['entry-1']['v']);
    }

    public function testReadRejectsUnknownType(): void
    {
        $storage = $this->getStorage(new DebuggerIdGenerator());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown storage type "summary; DROP TABLE entries".');

        $storage->read('summary; DROP TABLE entries', 'any');
    }

    public function testReadAllRejectsUnknownType(): void
    {
        $storage = $this->getStorage(new DebuggerIdGenerator());

        $this->expectException(\InvalidArgumentException::class);

        $storage->read('not-a-real-column');
    }

    public function getStorage(DebuggerIdGenerator $idGenerator): SqliteStorage
    {
        return new SqliteStorage($this->path, $idGenerator);
    }
}

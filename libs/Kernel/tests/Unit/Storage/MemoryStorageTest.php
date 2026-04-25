<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;

final class MemoryStorageTest extends AbstractStorageTestCase
{
    public function getStorage(DebuggerIdGenerator $idGenerator): StorageInterface
    {
        return new MemoryStorage($idGenerator);
    }

    public function testSummaryCount(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->addCollector($collector1 = $this->createFakeSummaryCollector(['test' => 'test']));
        $storage->addCollector($collector2 = $this->createFakeCollector(['test' => 'test']));

        $result = $storage->read(StorageInterface::TYPE_SUMMARY, null);
        $this->assertCount(1, $result);

        $this->assertEquals(
            [
                $idGenerator->getId() => [
                    'id' => $idGenerator->getId(),
                    'collectors' => [
                        ['id' => $collector1->getId(), 'name' => $collector1->getName()],
                        ['id' => $collector2->getId(), 'name' => $collector2->getName()],
                    ],
                ],
            ],
            $result,
        );
    }

    public function testReadObjectsTypeReturnsCollectorData(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector(['key' => 'value']);
        $storage->addCollector($collector);

        $result = $storage->read(StorageInterface::TYPE_OBJECTS);
        $this->assertCount(1, $result);
        $this->assertArrayHasKey($idGenerator->getId(), $result);
    }

    public function testReadObjectsTypeEmptyCollectors(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $result = $storage->read(StorageInterface::TYPE_OBJECTS);
        $this->assertCount(1, $result);
        $this->assertSame([], $result[$idGenerator->getId()]);
    }

    public function testWriteAndReadBack(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('entry-1', ['id' => 'entry-1'], ['data' => 'val'], ['obj' => 'info']);

        // Read summary for specific entry
        $result = $storage->read(StorageInterface::TYPE_SUMMARY, 'entry-1');
        $this->assertArrayHasKey('entry-1', $result);
        $this->assertSame(['id' => 'entry-1'], $result['entry-1']);

        // Read data
        $result = $storage->read(StorageInterface::TYPE_DATA, 'entry-1');
        $this->assertSame(['data' => 'val'], $result['entry-1']);

        // Read objects
        $result = $storage->read(StorageInterface::TYPE_OBJECTS, 'entry-1');
        $this->assertSame(['obj' => 'info'], $result['entry-1']);
    }

    public function testWriteMultipleEntriesAndReadAll(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('entry-1', ['id' => 'entry-1'], ['d' => 1], []);
        $storage->write('entry-2', ['id' => 'entry-2'], ['d' => 2], []);

        $result = $storage->read(StorageInterface::TYPE_SUMMARY);
        // 2 written + 1 from current idGenerator session
        $this->assertCount(3, $result);
    }

    public function testReadNonExistentEntryReturnsEmptyType(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('entry-1', ['id' => 'entry-1'], ['d' => 1], []);

        $result = $storage->read(StorageInterface::TYPE_DATA, 'nonexistent');
        // nonexistent entry returns empty for that type
        $this->assertSame([], $result['nonexistent'] ?? []);
    }

    public function testReadByIdMatchingCurrentSession(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);
        $collector = $this->createFakeCollector(['test' => 'data']);
        $storage->addCollector($collector);

        $result = $storage->read(StorageInterface::TYPE_DATA, $idGenerator->getId());
        $this->assertArrayHasKey($idGenerator->getId(), $result);
    }

    public function testReadObjectsWithMultipleCollectors(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $collector1 = $this->createFakeCollector(['key1' => 'val1']);
        $collector2 = $this->createFakeCollector(['key2' => 'val2']);
        // Give collector2 a different ID
        $collector2->method('getId')->willReturn('Mock_Collector_2');
        $storage->addCollector($collector1);
        $storage->addCollector($collector2);

        $result = $storage->read(StorageInterface::TYPE_OBJECTS);
        $this->assertArrayHasKey($idGenerator->getId(), $result);
        // Objects merge all collector data
        $this->assertNotEmpty($result[$idGenerator->getId()]);
    }

    public function testReadWrittenEntryObjectsType(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('written-id', ['id' => 'written-id'], ['data' => 'test'], ['obj1' => 'info1']);

        $result = $storage->read(StorageInterface::TYPE_OBJECTS, 'written-id');
        $this->assertSame(['obj1' => 'info1'], $result['written-id']);
    }

    public function testReadSummaryForCurrentSessionWithCollectors(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $collector = $this->createFakeCollector(['x' => 'y']);
        $storage->addCollector($collector);

        $result = $storage->read(StorageInterface::TYPE_SUMMARY, $idGenerator->getId());
        $this->assertArrayHasKey($idGenerator->getId(), $result);
        $this->assertSame($idGenerator->getId(), $result[$idGenerator->getId()]['id']);
        $this->assertNotEmpty($result[$idGenerator->getId()]['collectors']);
    }

    public function testReadWrittenEntryMissingType(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('entry-x', ['id' => 'entry-x'], ['d' => 1], ['o' => 2]);

        // Read a type that doesn't match any stored type key for a written entry
        $result = $storage->read('nonexistent_type', 'entry-x');
        $this->assertSame([], $result['entry-x']);
    }

    public function testFlushClearsCollectors(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $collector = $this->createFakeCollector(['data' => 'value']);
        $storage->addCollector($collector);

        $this->assertNotEmpty($storage->getData());
        $storage->flush();
        $this->assertSame([], $storage->getData());
    }

    public function testReadSummaryForNonCurrentSession(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        // Write an entry with a different ID
        $storage->write('other-id', ['id' => 'other-id', 'status' => 200], ['d' => 1], ['o' => 1]);

        // Read summary for a specific non-current-session ID
        $result = $storage->read(StorageInterface::TYPE_SUMMARY, 'other-id');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('other-id', $result);
        $this->assertSame(['id' => 'other-id', 'status' => 200], $result['other-id']);
    }

    public function testReadDataForNonCurrentSession(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('other-id', ['id' => 'other-id'], ['collector' => 'data'], ['obj' => 'info']);

        // Read data for the non-current-session entry
        $result = $storage->read(StorageInterface::TYPE_DATA, 'other-id');
        $this->assertCount(1, $result);
        $this->assertArrayHasKey('other-id', $result);
        $this->assertSame(['collector' => 'data'], $result['other-id']);
    }

    public function testReadObjectsForNonCurrentSession(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $storage->write('other-id', ['id' => 'other-id'], ['d' => 1], ['obj-key' => 'obj-val']);

        // Read objects for the non-current-session entry
        $result = $storage->read(StorageInterface::TYPE_OBJECTS, 'other-id');
        $this->assertCount(1, $result);
        $this->assertSame(['obj-key' => 'obj-val'], $result['other-id']);
    }

    public function testReadAllIncludesBothWrittenAndCurrentSession(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->getStorage($idGenerator);

        $collector = $this->createFakeCollector(['live' => 'data']);
        $storage->addCollector($collector);

        $storage->write('entry-a', ['id' => 'entry-a'], ['a' => 1], []);
        $storage->write('entry-b', ['id' => 'entry-b'], ['b' => 2], []);

        // Read all data entries (no ID filter)
        $result = $storage->read(StorageInterface::TYPE_DATA);
        $this->assertCount(3, $result); // 2 written + 1 current session
        $this->assertArrayHasKey('entry-a', $result);
        $this->assertArrayHasKey('entry-b', $result);
        $this->assertArrayHasKey($idGenerator->getId(), $result);
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
}

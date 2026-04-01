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
}

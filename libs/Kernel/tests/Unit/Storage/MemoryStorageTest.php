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
}

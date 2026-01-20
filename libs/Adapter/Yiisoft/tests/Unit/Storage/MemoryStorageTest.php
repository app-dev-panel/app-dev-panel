<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Storage;

use Yiisoft\Yii\Debug\DebuggerIdGenerator;
use Yiisoft\Yii\Debug\Storage\MemoryStorage;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

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
                    'collectors' => [$collector1->getName(), $collector2->getName()],
                ],
            ],
            $result
        );
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Storage;

use Yiisoft\Aliases\Aliases;
use Yiisoft\Files\FileHelper;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;

final class FileStorageTest extends AbstractStorageTestCase
{
    private string $path = __DIR__ . '/runtime';

    protected function tearDown(): void
    {
        parent::tearDown();
        FileHelper::removeDirectory($this->path);
    }

    /**
     * @dataProvider dataProvider()
     */
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

    /**
     * @dataProvider dataProvider()
     */
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

    /**
     * @dataProvider dataProvider()
     */
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

    public function getStorage(DebuggerIdGenerator $idGenerator): FileStorage
    {
        return new FileStorage(
            (new Aliases())->get($this->path),
            $idGenerator,
        );
    }
}

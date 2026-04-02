<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Repository;

use AppDevPanel\Api\Debug\Repository\CollectorRepository;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;

final class CollectorRepositoryTest extends TestCase
{
    public function testSummary(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'testId' => ['total' => 7],
            ]);

        $repository = new CollectorRepository($storage);

        $this->assertSame(
            [
                ['total' => 7],
            ],
            $repository->getSummary(),
        );
    }

    public function testSummaryEmpty(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn([]);

        $repository = new CollectorRepository($storage);

        $this->assertSame([], $repository->getSummary());
    }

    public function testDetail(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'testId' => ['stub' => ['key' => 'value']],
            ]);

        $repository = new CollectorRepository($storage);

        $this->assertSame(['stub' => ['key' => 'value']], $repository->getDetail('testId'));
    }

    public function testDumpObject(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'testId' => ['object' => []],
            ]);

        $repository = new CollectorRepository($storage);

        $this->assertSame(['object' => []], $repository->getDumpObject('testId'));
    }

    public function testObject(): void
    {
        $objectId = '123';
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'testId' => ['stdClass#' . $objectId => 'value'],
            ]);

        $repository = new CollectorRepository($storage);

        $object = $repository->getObject('testId', $objectId);
        $this->assertIsArray($object);
        $this->assertSame(
            [
                'stdClass',
                'value',
            ],
            $object,
        );
    }

    public function testObjectNotFound(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'testId' => [],
            ]);

        $repository = new CollectorRepository($storage);

        $this->assertNull($repository->getObject('testId', '999'));
    }

    public function testGetSummaryWithId(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'testId' => ['id' => 'testId', 'url' => '/test'],
            ]);

        $repository = new CollectorRepository($storage);
        $result = $repository->getSummary('testId');

        $this->assertSame(['id' => 'testId', 'url' => '/test'], $result);
    }

    public function testGetSummaryNotFoundThrowsException(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn([]);

        $repository = new CollectorRepository($storage);

        $this->expectException(\AppDevPanel\Api\Debug\Exception\NotFoundException::class);
        $this->expectExceptionMessage('nonexistent');
        $repository->getSummary('nonexistent');
    }

    public function testGetDetailNotFoundThrowsException(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')->willReturn([]);

        $repository = new CollectorRepository($storage);

        $this->expectException(\AppDevPanel\Api\Debug\Exception\NotFoundException::class);
        $repository->getDetail('nonexistent');
    }

    public function testSummaryReversed(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage
            ->method('read')
            ->willReturn([
                'id1' => ['id' => 'id1'],
                'id2' => ['id' => 'id2'],
                'id3' => ['id' => 'id3'],
            ]);

        $repository = new CollectorRepository($storage);
        $result = $repository->getSummary();

        // Should be reversed
        $this->assertSame(['id' => 'id3'], $result[0]);
        $this->assertSame(['id' => 'id1'], $result[2]);
    }
}

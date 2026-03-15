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
        $storage->method('read')
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
        $storage->method('read')
            ->willReturn([]);

        $repository = new CollectorRepository($storage);

        $this->assertSame([], $repository->getSummary());
    }

    public function testDetail(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')
            ->willReturn([
                'testId' => ['stub' => ['key' => 'value']],
            ]);

        $repository = new CollectorRepository($storage);

        $this->assertSame(['stub' => ['key' => 'value']], $repository->getDetail('testId'));
    }

    public function testDumpObject(): void
    {
        $storage = $this->createStub(StorageInterface::class);
        $storage->method('read')
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
        $storage->method('read')
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
        $storage->method('read')
            ->willReturn([
                'testId' => [],
            ]);

        $repository = new CollectorRepository($storage);

        $this->assertNull($repository->getObject('testId', '999'));
    }
}

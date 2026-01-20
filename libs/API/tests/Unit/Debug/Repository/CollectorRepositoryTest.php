<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Debug\Repository;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Api\Debug\Repository\CollectorRepository;
use Yiisoft\Yii\Debug\Storage\MemoryStorage;

final class CollectorRepositoryTest extends TestCase
{
    public function testSummary(): void
    {
        $storage = new MemoryStorage();
        $repository = new CollectorRepository($storage);

        $this->assertSame([], $repository->getSummary());

        $storage->write('testId', ['stub' => ['key' => 'value']], [], ['total' => 7]);

        $this->assertSame(
            [
                ['total' => 7],
            ],
            $repository->getSummary()
        );
    }

    public function testDetail(): void
    {
        $storage = new MemoryStorage();
        $storage->write('testId', ['stub' => ['key' => 'value']], [], ['total' => 7]);

        $repository = new CollectorRepository($storage);

        $this->assertSame(
            ['stub' => ['key' => 'value']],
            $repository->getDetail('testId')
        );
    }

    public function testDumpObject(): void
    {
        $storage = new MemoryStorage();
        $storage->write('testId', ['stub' => ['key' => 'value']], ['object' => []], ['total' => 7]);

        $repository = new CollectorRepository($storage);

        $this->assertSame(
            ['object' => []],
            $repository->getDumpObject('testId')
        );
    }

    public function testObject(): void
    {
        $storage = new MemoryStorage();

        $objectId = '123';
        $storage->write(
            'testId',
            ['stub' => ['key' => 'value']],
            ['stdClass#' . $objectId => 'value'],
            ['total' => 7],
        );

        $repository = new CollectorRepository($storage);

        $object = $repository->getObject('testId', $objectId);
        $this->assertIsArray($object);
        $this->assertSame(
            [
                'stdClass',
                'value',
            ],
            $object
        );
    }
}

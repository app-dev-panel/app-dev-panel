<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Cli\Command\DebugDumpCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DebugDumpCommandTest extends TestCase
{
    public function testViewDump(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDumpObject')
            ->with('entry-1')
            ->willReturn([
                'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['data' => true],
            ]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Object Dump: entry-1', $display);
        $this->assertStringContainsString('LogCollector', $display);
    }

    public function testViewDumpEmpty(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn([]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No dumped objects found', $tester->getDisplay());
    }

    public function testViewDumpJson(): void
    {
        $data = ['collector' => ['key' => 'value']];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willReturn($data);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['key' => 'value'], $decoded['collector']);
    }

    public function testViewDumpWithCollectorFilter(): void
    {
        $collectorClass = 'AppDevPanel\\Kernel\\Collector\\LogCollector';
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDumpObject')
            ->willReturn([
                $collectorClass => ['messages' => ['hello']],
                'OtherCollector' => ['data' => true],
            ]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', '--collector' => $collectorClass]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('LogCollector', $display);
    }

    public function testViewDumpCollectorNotFound(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDumpObject')
            ->willReturn([
                'ExistingCollector' => ['data' => true],
            ]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', '--collector' => 'NonExistent']);

        $this->assertSame(1, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Collector "NonExistent" not found', $display);
        $this->assertStringContainsString('ExistingCollector', $display);
    }

    public function testViewDumpError(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDumpObject')->willThrowException(new \RuntimeException('Not found'));

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'missing']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Not found', $tester->getDisplay());
    }

    public function testViewObject(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getObject')
            ->with('entry-1', 'obj-42')
            ->willReturn(['App\\User', ['id' => 1, 'name' => 'John']]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', 'objectId' => 'obj-42']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('App\\User', $display);
    }

    public function testViewObjectJson(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getObject')->willReturn(['App\\User', ['id' => 1]]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', 'objectId' => 'obj-1', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('App\\User', $decoded['class']);
    }

    public function testViewObjectNotFound(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getObject')->willReturn(null);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', 'objectId' => 'missing']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Object "missing" not found', $tester->getDisplay());
    }

    public function testViewObjectError(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getObject')->willThrowException(new \RuntimeException('Storage error'));

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', 'objectId' => 'obj-1']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Storage error', $tester->getDisplay());
    }

    public function testViewDumpWithEmptyCollectorValue(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDumpObject')
            ->willReturn([
                'EmptyCollector' => [],
            ]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('EmptyCollector', $display);
        $this->assertStringContainsString('(empty)', $display);
    }

    public function testViewDumpFilteredJson(): void
    {
        $collectorClass = 'LogCollector';
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getDumpObject')
            ->willReturn([
                $collectorClass => ['messages' => ['hello']],
                'OtherCollector' => ['data' => true],
            ]);

        $tester = new CommandTester(new DebugDumpCommand($repository));
        $tester->execute(['id' => 'entry-1', '--collector' => $collectorClass, '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey($collectorClass, $decoded);
        $this->assertArrayNotHasKey('OtherCollector', $decoded);
    }
}

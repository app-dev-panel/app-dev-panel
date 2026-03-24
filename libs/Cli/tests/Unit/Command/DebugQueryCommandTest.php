<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Cli\Command\DebugQueryCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DebugQueryCommandTest extends TestCase
{
    public function testListEntriesEmpty(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn([]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No debug entries', $tester->getDisplay());
    }

    public function testListEntriesTable(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturn([
                [
                    'id' => 'abc123',
                    'request' => ['method' => 'POST', 'url' => '/api/test', 'responseStatusCode' => '200'],
                    'logger' => ['total' => 3],
                    'event' => ['total' => 0],
                    'timeline' => ['total' => 0],
                ],
            ]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('abc123', $display);
        $this->assertStringContainsString('POST', $display);
        $this->assertStringContainsString('/api/test', $display);
        $this->assertStringContainsString('logs:3', $display);
    }

    public function testListEntriesJson(): void
    {
        $entry = [
            'id' => 'xyz789',
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn([$entry]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $this->assertSame('xyz789', $decoded[0]['id']);
    }

    public function testListEntriesWithLimit(): void
    {
        $entries = [];
        for ($i = 0; $i < 10; $i++) {
            $entries[] = [
                'id' => "entry-{$i}",
                'request' => ['method' => 'GET', 'url' => "/path/{$i}", 'responseStatusCode' => '200'],
            ];
        }
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($entries);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list', '--limit' => '3', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertCount(3, $decoded);
    }

    public function testListSkipsNonArrayEntries(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn(['not-an-array', null]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testUnknownAction(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }

    public function testViewEntryRequiresId(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'view']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Entry ID is required', $tester->getDisplay());
    }

    public function testViewEntryFull(): void
    {
        $data = [
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['messages' => [['level' => 'info']]],
            'AppDevPanel\\Kernel\\Collector\\EventCollector' => [],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->with('entry-1')->willReturn($data);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'view', 'id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Debug Entry: entry-1', $display);
        $this->assertStringContainsString('LogCollector', $display);
        $this->assertStringContainsString('(empty)', $display);
    }

    public function testViewEntryJson(): void
    {
        $data = ['collector' => ['key' => 'value']];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->with('entry-2')->willReturn($data);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'view', 'id' => 'entry-2', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['key' => 'value'], $decoded['collector']);
    }

    public function testViewEntryWithCollectorFilter(): void
    {
        $collectorClass = 'AppDevPanel\\Kernel\\Collector\\LogCollector';
        $data = [
            $collectorClass => ['messages' => [['level' => 'error', 'message' => 'fail']]],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->willReturn($data);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute([
            'action' => 'view',
            'id' => 'entry-3',
            '--collector' => $collectorClass,
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('error', $display);
        $this->assertStringContainsString('fail', $display);
    }

    public function testViewEntryWithCollectorFilterJson(): void
    {
        $collectorClass = 'AppDevPanel\\Kernel\\Collector\\LogCollector';
        $data = [
            $collectorClass => ['messages' => ['hello']],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->willReturn($data);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute([
            'action' => 'view',
            'id' => 'entry-4',
            '--collector' => $collectorClass,
            '--json' => true,
        ]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame(['messages' => ['hello']], $decoded);
    }

    public function testViewEntryCollectorNotFound(): void
    {
        $data = [
            'AppDevPanel\\Kernel\\Collector\\LogCollector' => ['data' => true],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->willReturn($data);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute([
            'action' => 'view',
            'id' => 'entry-5',
            '--collector' => 'NonExistentCollector',
        ]);

        $this->assertSame(1, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Collector "NonExistentCollector" not found', $display);
        $this->assertStringContainsString('LogCollector', $display);
    }

    public function testViewEntryThrowsException(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getDetail')->willThrowException(new \RuntimeException('Entry not found'));

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'view', 'id' => 'missing']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Entry not found', $tester->getDisplay());
    }

    public function testFormatCollectorsWithException(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturn([
                [
                    'id' => 'err-1',
                    'request' => ['method' => 'GET', 'url' => '/error', 'responseStatusCode' => '500'],
                    'logger' => ['total' => 1],
                    'event' => ['total' => 5],
                    'timeline' => ['total' => 2],
                    'exception' => ['class' => 'RuntimeException'],
                ],
            ]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('logs:1', $display);
        $this->assertStringContainsString('events:5', $display);
        $this->assertStringContainsString('timeline:2', $display);
        $this->assertStringContainsString('exception', $display);
    }

    public function testListEntryWithoutRequestInfo(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturn([
                [
                    'id' => 'no-req-1',
                    'logger' => ['total' => 0],
                ],
            ]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('no-req-1', $display);
    }

    public function testExtractRequestInfoFromCommandSummary(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturn([
                [
                    'id' => 'cmd-1',
                    'command' => ['method' => 'CLI', 'url' => 'app:migrate', 'responseStatusCode' => '0'],
                ],
            ]);

        $tester = new CommandTester(new DebugQueryCommand($repository));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('CLI', $display);
        $this->assertStringContainsString('app:migrate', $display);
    }
}

<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Cli\Command\DebugSummaryCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DebugSummaryCommandTest extends TestCase
{
    public function testSummaryWeb(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/api/test', 'responseStatusCode' => '200'],
            'timeline' => ['duration' => 123.45, 'memory' => 1048576, 'memoryPeak' => 2097152, 'total' => 5],
            'logger' => ['total' => 3],
            'event' => ['total' => 10],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->with('entry-1')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Debug Entry Summary: entry-1', $display);
        $this->assertStringContainsString('GET', $display);
        $this->assertStringContainsString('/api/test', $display);
        $this->assertStringContainsString('200', $display);
        $this->assertStringContainsString('Log entries', $display);
    }

    public function testSummaryConsole(): void
    {
        $data = [
            'command' => ['method' => 'CLI', 'url' => 'app:migrate', 'responseStatusCode' => '0'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'cmd-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('CLI', $display);
        $this->assertStringContainsString('app:migrate', $display);
    }

    public function testSummaryWithException(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/error', 'responseStatusCode' => '500'],
            'exception' => [
                'class' => 'RuntimeException',
                'message' => 'Something went wrong',
                'file' => '/app/src/Controller.php',
                'line' => 42,
            ],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'err-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('RuntimeException', $display);
        $this->assertStringContainsString('Something went wrong', $display);
        $this->assertStringContainsString('Controller.php', $display);
    }

    public function testSummaryJson(): void
    {
        $data = ['request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200']];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('GET', $decoded['request']['method']);
    }

    public function testSummaryError(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willThrowException(new \RuntimeException('Not found'));

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'missing']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Not found', $tester->getDisplay());
    }

    public function testSummaryWithDbAndCache(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'db' => ['total' => 15],
            'cache' => ['total' => 3],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('DB Queries', $display);
        $this->assertStringContainsString('15', $display);
        $this->assertStringContainsString('Cache Operations', $display);
    }
}

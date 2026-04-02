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

    public function testSummaryWithMailerAndQueue(): void
    {
        $data = [
            'request' => ['method' => 'POST', 'url' => '/send', 'responseStatusCode' => '200'],
            'mailer' => ['total' => 2],
            'queue' => ['total' => 5],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Emails', $display);
        $this->assertStringContainsString('Queue Jobs', $display);
    }

    public function testSummaryWithSmallMemory(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'timeline' => ['duration' => 1.5, 'memory' => 512, 'memoryPeak' => 800],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('512 B', $display);
        $this->assertStringContainsString('800 B', $display);
    }

    public function testSummaryWithKBMemory(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'timeline' => ['memory' => 51200],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('50.0 KB', $tester->getDisplay());
    }

    public function testSummaryWithoutRequestInfo(): void
    {
        $data = ['logger' => ['total' => 5]];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Log entries', $display);
    }

    public function testSummaryExceptionWithoutFile(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '500'],
            'exception' => ['class' => 'LogicException', 'message' => 'Bad logic'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('LogicException', $display);
        $this->assertStringContainsString('Bad logic', $display);
    }

    public function testSummaryWebKey(): void
    {
        $data = [
            'web' => ['method' => 'PUT', 'url' => '/update', 'responseStatusCode' => '200', 'duration' => 50.0],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('PUT', $display);
        $this->assertStringContainsString('/update', $display);
    }

    public function testSummaryWithMBMemory(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'timeline' => ['memory' => 5_242_880], // 5 MB
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('5.0 MB', $tester->getDisplay());
    }

    public function testSummaryTimingFromRequestFallback(): void
    {
        $data = [
            'request' => [
                'method' => 'GET',
                'url' => '/fallback',
                'responseStatusCode' => '200',
                'duration' => 99.9,
                'memory' => 2048,
            ],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('99.90 ms', $display);
        $this->assertStringContainsString('2.0 KB', $display);
    }

    public function testSummaryNoTimingSection(): void
    {
        // Data with no timeline/web/request timing keys
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        // Should not show Timing section when no timing data exists
        $this->assertStringNotContainsString('Timing', $tester->getDisplay());
    }

    public function testSummaryEmptyData(): void
    {
        $data = [];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Debug Entry Summary: entry-1', $tester->getDisplay());
    }

    public function testSummaryWithOnlyDuration(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'timeline' => ['duration' => 42.5],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('42.50 ms', $display);
        $this->assertStringNotContainsString('Memory Peak', $display);
    }

    public function testSummaryTimingNonArraySkipped(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'timeline' => 'invalid',
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());

        // web fallback would be used, which is also not present - no timing section
    }

    public function testSummaryTimingFromWebFallback(): void
    {
        $data = [
            'web' => [
                'method' => 'GET',
                'url' => '/web-fallback',
                'responseStatusCode' => '200',
                'duration' => 77.7,
                'memory' => 4096,
                'memoryPeak' => 8192,
            ],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('77.70 ms', $display);
        $this->assertStringContainsString('4.0 KB', $display);
        $this->assertStringContainsString('8.0 KB', $display);
    }

    public function testSummaryWithOnlyMemoryPeak(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'timeline' => ['memoryPeak' => 10_485_760], // 10 MB
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Memory Peak', $display);
        $this->assertStringContainsString('10.0 MB', $display);
    }

    public function testSummaryExceptionEmptyArray(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '500'],
            'exception' => [],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        // Exception section should NOT appear since 'class' key is missing
        $this->assertStringNotContainsString('Exception', $tester->getDisplay());
    }

    public function testSummaryExceptionNonArray(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '500'],
            'exception' => 'not an array',
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringNotContainsString('Exception', $tester->getDisplay());
    }

    public function testSummaryExceptionWithFileLine(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '500'],
            'exception' => [
                'class' => 'InvalidArgumentException',
                'message' => 'Bad input',
                'file' => '/app/src/Service.php',
                'line' => 99,
            ],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('at /app/src/Service.php:99', $display);
    }

    public function testSummaryNoCollectorsSection(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringNotContainsString('Collectors', $tester->getDisplay());
    }

    public function testSummaryWithZeroTotals(): void
    {
        $data = [
            'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
            'logger' => ['total' => 0],
            'event' => ['total' => 0],
            'db' => ['total' => 0],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        // Zero totals should not show in collectors section
        $this->assertStringNotContainsString('Log entries', $tester->getDisplay());
        $this->assertStringNotContainsString('Events', $tester->getDisplay());
    }

    public function testSummaryCommandKey(): void
    {
        $data = [
            'command' => [
                'method' => 'CLI',
                'url' => 'cache:clear',
                'responseStatusCode' => '0',
            ],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('console', $display);
        $this->assertStringContainsString('cache:clear', $display);
    }

    public function testSummaryRequestInfoMissingFields(): void
    {
        $data = [
            'request' => [],
        ];
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository->method('getSummary')->willReturn($data);

        $tester = new CommandTester(new DebugSummaryCommand($repository));
        $tester->execute(['id' => 'entry-1']);

        $this->assertSame(0, $tester->getStatusCode());
        // Should show dash for missing fields
        $display = $tester->getDisplay();
        $this->assertStringContainsString("\xe2\x80\x94", $display); // em dash character
    }
}

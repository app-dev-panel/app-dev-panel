<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use AppDevPanel\Cli\Command\DebugTailCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DebugTailCommandTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $command = new DebugTailCommand($repository);

        $this->assertSame('debug:tail', $command->getName());
        $this->assertSame('Watch debug entries in real-time', $command->getDescription());
    }

    public function testCommandHasOptions(): void
    {
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $command = new DebugTailCommand($repository);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('interval'));
        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasOption('count'));
    }

    public function testTailWithCountRendersNewEntriesFormatted(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'new-1') {
                    return [
                        'request' => ['method' => 'GET', 'url' => '/api/test', 'responseStatusCode' => '200'],
                        'logger' => ['total' => 2],
                    ];
                }

                $callCount++;
                if ($callCount <= 1) {
                    return [['id' => 'old-1']]; // Initial known IDs
                }

                return [['id' => 'old-1'], ['id' => 'new-1']]; // New entry appears
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Watching debug entries', $display);
        $this->assertStringContainsString('200', $display);
        $this->assertStringContainsString('GET', $display);
        $this->assertStringContainsString('/api/test', $display);
        $this->assertStringContainsString('logs:2', $display);
    }

    public function testTailWithCountRendersNewEntriesJson(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'entry-1') {
                    return ['request' => ['method' => 'POST', 'url' => '/data', 'responseStatusCode' => '201']];
                }

                $callCount++;
                if ($callCount <= 1) {
                    return [];
                }

                return [['id' => 'entry-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        // JSON output should contain the summary data
        $this->assertStringContainsString('POST', $display);
        $this->assertStringContainsString('/data', $display);
    }

    public function testTailRenders4xxStatus(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'err-1') {
                    return ['request' => ['method' => 'GET', 'url' => '/missing', 'responseStatusCode' => '404']];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'err-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('404', $tester->getDisplay());
    }

    public function testTailRenders5xxStatus(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'err-1') {
                    return ['request' => ['method' => 'GET', 'url' => '/error', 'responseStatusCode' => '500']];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'err-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('500', $tester->getDisplay());
    }

    public function testTailRendersRedirectStatus(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'r-1') {
                    return ['request' => ['method' => 'GET', 'url' => '/old', 'responseStatusCode' => '301']];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'r-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('301', $tester->getDisplay());
    }

    public function testTailRendersException(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'exc-1') {
                    return [
                        'request' => ['method' => 'GET', 'url' => '/fail', 'responseStatusCode' => '200'],
                        'exception' => ['class' => 'RuntimeException'],
                    ];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'exc-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('exception', $display);
    }

    public function testTailRendersEntryWithoutRequestInfo(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'bare-1') {
                    return ['logger' => ['total' => 0]];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'bare-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testTailRendersCommandSummary(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'cmd-1') {
                    return ['command' => ['method' => 'CLI', 'url' => 'app:migrate', 'responseStatusCode' => '0']];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'cmd-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('CLI', $display);
        $this->assertStringContainsString('app:migrate', $display);
    }

    public function testTailSkipsNonArrayAndNonIdEntries(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'valid-1') {
                    return ['request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200']];
                }

                $callCount++;
                if ($callCount <= 1) {
                    return ['not-array', ['no-id' => true], null];
                }

                return ['not-array', ['no-id' => true], null, ['id' => 'valid-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testTailMultipleEntries(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id !== null) {
                    return ['request' => ['method' => 'GET', 'url' => "/{$id}", 'responseStatusCode' => '200']];
                }

                $callCount++;
                if ($callCount <= 1) {
                    return [];
                }

                return [['id' => 'a'], ['id' => 'b']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '2', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testTailWithEmptyCollectors(): void
    {
        $callCount = 0;
        $repository = $this->createMock(CollectorRepositoryInterface::class);
        $repository
            ->method('getSummary')
            ->willReturnCallback(function (?string $id = null) use (&$callCount) {
                if ($id === 'e-1') {
                    return [
                        'request' => ['method' => 'GET', 'url' => '/', 'responseStatusCode' => '200'],
                        'logger' => ['total' => 0],
                        'event' => ['total' => 0],
                        'timeline' => ['total' => 0],
                    ];
                }

                $callCount++;
                return $callCount <= 1 ? [] : [['id' => 'e-1']];
            });

        $tester = new CommandTester(new DebugTailCommand($repository));
        $tester->execute(['--count' => '1', '--interval' => '1']);

        $this->assertSame(0, $tester->getStatusCode());
    }
}

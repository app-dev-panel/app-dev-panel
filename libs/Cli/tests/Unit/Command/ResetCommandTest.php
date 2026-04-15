<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ResetCommandTest extends TestCase
{
    public function testCommandClearsStorage(): void
    {
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())->method('clear');
        $debugger = new Debugger(new DebuggerIdGenerator(), $storage, []);

        $tester = new CommandTester(new DebugResetCommand($storage, $debugger));

        $this->assertSame(Command::SUCCESS, $tester->execute([]));
    }

    public function testCommandSucceedsWhenStorageClearThrows(): void
    {
        // Sanity check: if storage cleanup fails, the command does not swallow
        // the exception — it surfaces to the caller so CI marks the run as
        // failed instead of silently leaving stale debug data.
        $storage = $this->createMock(StorageInterface::class);
        $storage->method('clear')->willThrowException(new \RuntimeException('disk full'));
        $debugger = new Debugger(new DebuggerIdGenerator(), $storage, []);

        $tester = new CommandTester(new DebugResetCommand($storage, $debugger));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('disk full');
        $tester->execute([]);
    }

    public function testCommandNameConstantMatchesAttribute(): void
    {
        $this->assertSame('debug:reset', DebugResetCommand::COMMAND_NAME);
    }

    public function testCommandExposesHelpText(): void
    {
        $command = new DebugResetCommand(
            $this->createMock(StorageInterface::class),
            new Debugger(new DebuggerIdGenerator(), $this->createMock(StorageInterface::class), []),
        );

        $this->assertStringContainsString('debug storage', $command->getHelp());
    }
}

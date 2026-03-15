<?php

declare(strict_types = 1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugResetCommand;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class ResetCommandTest extends TestCase
{
    public function testCommand(): void
    {
        $idGenerator = new DebuggerIdGenerator();
        $storage = $this->createMock(StorageInterface::class);
        $storage->expects($this->once())->method('clear');
        $debugger = new Debugger($idGenerator, $storage, []);

        $command = new DebugResetCommand($storage, $debugger);

        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
    }
}

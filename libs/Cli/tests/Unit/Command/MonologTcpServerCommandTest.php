<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\MonologTcpServerCommand;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\MemoryStorage;
use PHPUnit\Framework\TestCase;

final class MonologTcpServerCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $this->assertSame('monolog:serve', MonologTcpServerCommand::COMMAND_NAME);
    }

    public function testDefaultConstants(): void
    {
        $this->assertSame('0.0.0.0', MonologTcpServerCommand::DEFAULT_HOST);
        $this->assertSame(9913, MonologTcpServerCommand::DEFAULT_PORT);
    }

    public function testCanInstantiate(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $command = new MonologTcpServerCommand($storage);

        $this->assertSame('monolog:serve', $command->getName());
        $this->assertSame('Start a TCP server for Monolog log messages', $command->getDescription());
    }

    public function testHasHostAndPortOptions(): void
    {
        $storage = new MemoryStorage(new DebuggerIdGenerator());
        $command = new MonologTcpServerCommand($storage);
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('host'));
        $this->assertTrue($definition->hasOption('port'));
        $this->assertSame('0.0.0.0', $definition->getOption('host')->getDefault());
        $this->assertSame(9913, $definition->getOption('port')->getDefault());
    }
}

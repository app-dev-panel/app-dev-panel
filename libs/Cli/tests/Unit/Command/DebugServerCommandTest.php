<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugServerCommand;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[RequiresPhpExtension('sockets')]
final class DebugServerCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $this->assertSame('dev', DebugServerCommand::COMMAND_NAME);
    }

    public function testTestEnvReturnsOk(): void
    {
        $command = new DebugServerCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testCustomAddressAndPort(): void
    {
        $command = new DebugServerCommand('127.0.0.1', 9999);
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }
}

<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class DebugServerBroadcastCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $this->assertSame('dev:broadcast', DebugServerBroadcastCommand::COMMAND_NAME);
    }

    public function testTestEnvReturnsOk(): void
    {
        $command = new DebugServerBroadcastCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }
}

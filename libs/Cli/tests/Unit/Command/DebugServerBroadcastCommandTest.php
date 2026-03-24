<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use AppDevPanel\Kernel\DebugServer\Broadcaster;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
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

    public function testWithCustomLogger(): void
    {
        $command = new DebugServerBroadcastCommand(new NullLogger());
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDefaultOptions(): void
    {
        $command = new DebugServerBroadcastCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('message'));
        $this->assertSame('m', $definition->getOption('message')->getShortcut());
        $this->assertSame('Test message', $definition->getOption('message')->getDefault());
        $this->assertTrue($definition->hasOption('env'));
    }

    public function testOutputContainsTitle(): void
    {
        $command = new DebugServerBroadcastCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
    }

    public function testBroadcastExecutesWithDefaultMessage(): void
    {
        // Real Broadcaster is safe — no socket files exist, so broadcast is a no-op
        $command = new DebugServerBroadcastCommand(null, new Broadcaster());
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
    }

    public function testBroadcastExecutesWithCustomMessage(): void
    {
        $command = new DebugServerBroadcastCommand(null, new Broadcaster());
        $tester = new CommandTester($command);

        $tester->execute(['--message' => 'Hello world']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testBroadcastWithLoggerAndBroadcaster(): void
    {
        $command = new DebugServerBroadcastCommand(new NullLogger(), new Broadcaster());
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
    }
}

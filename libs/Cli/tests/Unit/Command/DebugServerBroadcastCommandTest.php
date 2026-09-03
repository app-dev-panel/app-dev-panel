<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugServerBroadcastCommand;
use AppDevPanel\Kernel\DebugServer\BroadcasterInterface;
use AppDevPanel\Kernel\DebugServer\Connection;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DebugServerBroadcastCommandTest extends TestCase
{
    public function testCommandName(): void
    {
        $this->assertSame('dev:broadcast', DebugServerBroadcastCommand::COMMAND_NAME);
    }

    public function testTestEnvReturnsOk(): void
    {
        $broadcaster = $this->recordingBroadcaster();
        $command = new DebugServerBroadcastCommand(null, $broadcaster);
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertSame([], $broadcaster->calls, '--env=test must not broadcast');
    }

    public function testWithCustomLogger(): void
    {
        $command = new DebugServerBroadcastCommand(new NullLogger(), $this->recordingBroadcaster());
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDefaultOptions(): void
    {
        $command = new DebugServerBroadcastCommand(null, $this->recordingBroadcaster());
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('message'));
        $this->assertSame('m', $definition->getOption('message')->getShortcut());
        $this->assertSame('Test message', $definition->getOption('message')->getDefault());
        $this->assertTrue($definition->hasOption('env'));
    }

    public function testOutputContainsTitle(): void
    {
        $command = new DebugServerBroadcastCommand(null, $this->recordingBroadcaster());
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
    }

    public function testBroadcastExecutesWithDefaultMessage(): void
    {
        $broadcaster = $this->recordingBroadcaster();
        $command = new DebugServerBroadcastCommand(null, $broadcaster);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
        $this->assertSame(
            [
                [Connection::MESSAGE_TYPE_LOGGER,     'Test message'],
                [Connection::MESSAGE_TYPE_VAR_DUMPER, '{"$data":"Test message"}'],
            ],
            $broadcaster->calls,
        );
    }

    public function testBroadcastExecutesWithCustomMessage(): void
    {
        $broadcaster = $this->recordingBroadcaster();
        $command = new DebugServerBroadcastCommand(null, $broadcaster);
        $tester = new CommandTester($command);

        $tester->execute(['--message' => 'Hello wörld']);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertSame(
            [
                [Connection::MESSAGE_TYPE_LOGGER,     'Hello wörld'],
                [Connection::MESSAGE_TYPE_VAR_DUMPER, '{"$data":"Hello wörld"}'],
            ],
            $broadcaster->calls,
        );
    }

    public function testBroadcastWithLoggerAndBroadcaster(): void
    {
        $records = [];
        $logger = new class($records) extends AbstractLogger {
            public function __construct(
                private array &$records,
            ) {}

            public function log($level, Stringable|string $message, array $context = []): void
            {
                $this->records[] = [(string) $level, (string) $message];
            }
        };
        $broadcaster = $this->recordingBroadcaster();
        $command = new DebugServerBroadcastCommand($logger, $broadcaster);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
        $this->assertCount(2, $broadcaster->calls);
        $this->assertSame(
            [
                ['info', 'Starting broadcast.'],
                ['info', 'Broadcast complete.'],
            ],
            $records,
        );
    }

    public function testBroadcastErrorsAreReportedAndFailTheCommand(): void
    {
        $broadcaster = new class implements BroadcasterInterface {
            public function broadcast(int $type, string $data): array
            {
                return ['timeout' => 'Send timed out after 0.200s (receiver buffer full)'];
            }
        };
        $command = new DebugServerBroadcastCommand(null, $broadcaster);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(Command::FAILURE, $tester->getStatusCode());
        $this->assertStringContainsString('Send timed out', $tester->getDisplay());
    }

    /**
     * @return BroadcasterInterface&object{calls: list<array{0: int, 1: string}>}
     */
    private function recordingBroadcaster(): BroadcasterInterface
    {
        return new class implements BroadcasterInterface {
            /** @var list<array{0: int, 1: string}> */
            public array $calls = [];

            public function broadcast(int $type, string $data): array
            {
                $this->calls[] = [$type, $data];
                return [];
            }
        };
    }
}

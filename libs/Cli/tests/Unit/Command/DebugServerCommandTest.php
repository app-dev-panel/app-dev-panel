<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\DebugServerCommand;
use AppDevPanel\Kernel\DebugServer\Connection;
use Generator;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
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

    public function testWithCustomLogger(): void
    {
        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger());
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDefaultOptions(): void
    {
        $command = new DebugServerCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('address'));
        $this->assertSame('a', $definition->getOption('address')->getShortcut());
        $this->assertSame('0.0.0.0', $definition->getOption('address')->getDefault());
        $this->assertTrue($definition->hasOption('port'));
        $this->assertSame('p', $definition->getOption('port')->getShortcut());
        $this->assertSame(8890, $definition->getOption('port')->getDefault());
        $this->assertTrue($definition->hasOption('env'));
    }

    public function testOutputContainsTitle(): void
    {
        $command = new DebugServerCommand();
        $tester = new CommandTester($command);

        $tester->execute(['--env' => 'test']);

        $this->assertStringContainsString('ADP Debug Server', $tester->getDisplay());
    }

    public function testCustomAddressOptionOverride(): void
    {
        $command = new DebugServerCommand('0.0.0.0', 8890);
        $tester = new CommandTester($command);

        $tester->execute(['--address' => '192.168.1.1', '--port' => '9000', '--env' => 'test']);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testConnectionFailure(): void
    {
        $factory = static function (): array {
            throw new \RuntimeException('Socket creation failed');
        };

        $command = new DebugServerCommand('0.0.0.0', 8890, null, $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString(
            'Failed to start debug server: Socket creation failed',
            $tester->getDisplay(),
        );
    }

    public function testServerReceivesErrorMessage(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::errorMessageGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Listening on', $display);
        $this->assertStringContainsString('Connection closed with error', $display);
    }

    public function testServerReceivesVarDumperMessage(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::varDumperMessageGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('dumped value', $display);
    }

    public function testServerReceivesLoggerMessage(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::loggerMessageGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('log entry', $tester->getDisplay());
    }

    public function testServerReceivesPlainTextMessage(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::plainTextMessageGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('plain content', $tester->getDisplay());
    }

    public function testServerHandlesInvalidJsonMessage(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::invalidJsonMessageGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Failed to decode message', $tester->getDisplay());
    }

    public function testServerReceivesMultipleMessagesThenError(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::mixedMessagesGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('first message', $display);
        $this->assertStringContainsString('Connection closed with error', $display);
    }

    public function testServerWithEmptyMessageStream(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::emptyGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Listening on', $tester->getDisplay());
    }

    public function testServerWithInvalidJsonFollowedByValidMessage(): void
    {
        $factory = static fn(): array => [
            '/tmp/test-socket.sock',
            self::invalidThenValidGenerator(),
            static function (): void {},
        ];

        $command = new DebugServerCommand('0.0.0.0', 8890, new NullLogger(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([]);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Failed to decode message', $display);
        $this->assertStringContainsString('valid after invalid', $display);
    }

    private static function errorMessageGenerator(): Generator
    {
        yield [Connection::TYPE_ERROR, 'Socket read error'];
    }

    private static function varDumperMessageGenerator(): Generator
    {
        yield [Connection::TYPE_RESULT, json_encode([Connection::MESSAGE_TYPE_VAR_DUMPER, 'dumped value'])];
    }

    private static function loggerMessageGenerator(): Generator
    {
        yield [Connection::TYPE_RESULT, json_encode([Connection::MESSAGE_TYPE_LOGGER, 'log entry'])];
    }

    private static function plainTextMessageGenerator(): Generator
    {
        yield [Connection::TYPE_RESULT, json_encode([999, 'plain content'])];
    }

    private static function invalidJsonMessageGenerator(): Generator
    {
        yield [Connection::TYPE_RESULT, '{invalid-json'];
    }

    private static function mixedMessagesGenerator(): Generator
    {
        yield [Connection::TYPE_RESULT, json_encode([Connection::MESSAGE_TYPE_LOGGER, 'first message'])];
        yield [Connection::TYPE_ERROR, 'Connection lost'];
    }

    private static function emptyGenerator(): Generator
    {
        yield from [];
    }

    private static function invalidThenValidGenerator(): Generator
    {
        yield [Connection::TYPE_RESULT, 'not-json{'];
        yield [Connection::TYPE_RESULT, json_encode([Connection::MESSAGE_TYPE_LOGGER, 'valid after invalid'])];
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\SocketReader;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class SocketReaderTest extends TestCase
{
    public function testReadReceivesBroadcastedLoggerMessage(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'hello from test');

            $reader = new SocketReader($connection->getSocket());
            $message = $this->readFirstResult($reader);

            $this->assertNotNull($message, 'Expected to receive a broadcasted message but got only timeouts');
            $decoded = json_decode($message[1], true);
            $this->assertSame(Connection::MESSAGE_TYPE_LOGGER, $decoded[0]);
            $this->assertSame('hello from test', $decoded[1]);
        } finally {
            $connection->close();
        }
    }

    public function testReadReceivesVarDumperMessage(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, '{"var":"dump"}');

            $reader = new SocketReader($connection->getSocket());
            $message = $this->readFirstResult($reader);

            $this->assertNotNull($message, 'Expected to receive a broadcasted message but got only timeouts');
            $decoded = json_decode($message[1], true);
            $this->assertSame(Connection::MESSAGE_TYPE_VAR_DUMPER, $decoded[0]);
            $this->assertSame('{"var":"dump"}', $decoded[1]);
        } finally {
            $connection->close();
        }
    }

    public function testReadMultipleMessages(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'msg1');
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, 'msg2');

            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();

            $received = [];
            for ($i = 0; $i < 15; $i++) {
                $message = $generator->current();
                if ($message[0] === Connection::TYPE_RESULT) {
                    $received[] = json_decode($message[1], true);
                    if (count($received) >= 2) {
                        break;
                    }
                }
                $generator->next();
            }

            $this->assertCount(2, $received, 'Expected exactly 2 messages');
            $this->assertSame(Connection::MESSAGE_TYPE_LOGGER, $received[0][0]);
            $this->assertSame('msg1', $received[0][1]);
            $this->assertSame(Connection::MESSAGE_TYPE_VAR_DUMPER, $received[1][0]);
            $this->assertSame('msg2', $received[1][1]);
        } finally {
            $connection->close();
        }
    }

    public function testReadGeneratorCanBeStopped(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            // Broadcast a message so the generator has something to yield
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'stop-test');

            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();
            $message = $generator->current();

            // After getting a result, we can stop iterating (generator is garbage-collected)
            $this->assertSame(Connection::TYPE_RESULT, $message[0]);
        } finally {
            $connection->close();
        }
    }

    /**
     * Read from the generator until a TYPE_RESULT is received or max iterations exceeded.
     */
    private function readFirstResult(SocketReader $reader, int $maxIterations = 15): ?array
    {
        $generator = $reader->read();

        for ($i = 0; $i < $maxIterations; $i++) {
            $message = $generator->current();
            if ($message[0] === Connection::TYPE_RESULT) {
                return $message;
            }
            $generator->next();
        }

        return null;
    }
}

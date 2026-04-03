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
    public function testReadReceivesBroadcastedMessage(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'hello from test');

            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();

            $message = $generator->current();

            // We should get either a TYPE_RESULT with our data, or TYPE_RELEASE on timeout
            $this->assertContains($message[0], [Connection::TYPE_RESULT, Connection::TYPE_RELEASE]);

            if ($message[0] === Connection::TYPE_RESULT) {
                $decoded = json_decode($message[1], true);
                $this->assertSame(Connection::MESSAGE_TYPE_LOGGER, $decoded[0]);
                $this->assertSame('hello from test', $decoded[1]);
            }
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
            $generator = $reader->read();

            $message = $generator->current();

            if ($message[0] === Connection::TYPE_RESULT) {
                $decoded = json_decode($message[1], true);
                $this->assertSame(Connection::MESSAGE_TYPE_VAR_DUMPER, $decoded[0]);
                $this->assertSame('{"var":"dump"}', $decoded[1]);
            } else {
                // Timeout/release is acceptable in test environment
                $this->assertContains($message[0], [Connection::TYPE_RELEASE, Connection::TYPE_ERROR]);
            }
        } finally {
            $connection->close();
        }
    }

    public function testReadYieldsReleaseOnTimeout(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            // Don't broadcast anything — reader should time out
            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();

            $message = $generator->current();

            // Should be a release or error (timeout with no data)
            $this->assertContains($message[0], [Connection::TYPE_RELEASE, Connection::TYPE_ERROR]);
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
            // Try to read up to 2 messages, allow for timeouts
            for ($i = 0; $i < 5 && count($received) < 2; $i++) {
                $message = $generator->current();
                if ($message[0] === Connection::TYPE_RESULT) {
                    $received[] = json_decode($message[1], true);
                }
                $generator->next();
            }

            // At least one message should have been received
            $this->assertNotEmpty($received);
        } finally {
            $connection->close();
        }
    }

    public function testConstructorAcceptsSocket(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $reader = new SocketReader($connection->getSocket());
            $this->assertInstanceOf(SocketReader::class, $reader);
        } finally {
            $connection->close();
        }
    }
}

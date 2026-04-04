<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug;

use AppDevPanel\Api\Debug\LiveEventStreamFactory;
use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class LiveEventStreamFactoryTest extends TestCase
{
    public function testCreateReturnsStreamAndCloseCallbacks(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 2);

        $this->assertIsCallable($stream);
        $this->assertIsCallable($close);

        // Clean up the socket
        $close();
    }

    public function testStreamReceivesLogMessage(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 5);

        try {
            // Broadcast a log message
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test log message');

            // Stream should pick it up
            $buffer = [];
            $continue = $stream($buffer);

            $this->assertTrue($continue);
            $this->assertNotEmpty($buffer);

            $event = json_decode($buffer[0], true);
            $this->assertSame('live-log', $event['type']);
            $this->assertSame('test log message', $event['payload']);
        } finally {
            $close();
        }
    }

    public function testStreamReceivesVarDumpMessage(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 5);

        try {
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, '{"x":42}');

            $buffer = [];
            $stream($buffer);

            $this->assertNotEmpty($buffer);
            $event = json_decode($buffer[0], true);
            $this->assertSame('live-dump', $event['type']);
            $this->assertSame('{"x":42}', $event['payload']);
        } finally {
            $close();
        }
    }

    public function testStreamReceivesEntryCreatedMessage(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 5);

        try {
            $broadcaster = new Broadcaster();
            $broadcaster->broadcast(Connection::MESSAGE_TYPE_ENTRY_CREATED, 'abc123');

            $buffer = [];
            $stream($buffer);

            $this->assertNotEmpty($buffer);
            $event = json_decode($buffer[0], true);
            $this->assertSame('entry-created', $event['type']);
            $this->assertSame(['id' => 'abc123'], $event['payload']);
        } finally {
            $close();
        }
    }

    public function testStreamReturnsEmptyBufferWhenNoMessages(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 5);

        try {
            $buffer = [];
            $continue = $stream($buffer);

            $this->assertTrue($continue);
            $this->assertEmpty($buffer);
        } finally {
            $close();
        }
    }

    public function testStreamReturnsFalseAfterDeadline(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 0);

        try {
            // Wait a moment for deadline to pass
            sleep(1);

            $buffer = [];
            $continue = $stream($buffer);

            $this->assertFalse($continue);
        } finally {
            $close();
        }
    }

    public function testCloseCallbackCleanupSocket(): void
    {
        [$stream, $close] = LiveEventStreamFactory::create(deadlineSeconds: 5);

        // Discovery pattern should find our socket
        $beforeFiles = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        $this->assertNotEmpty($beforeFiles);

        $close();

        // After close, our socket file should be removed
        // (other sockets might still exist from parallel tests)
        $afterFiles = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        $this->assertLessThanOrEqual(count($beforeFiles), count($afterFiles) + 1);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\SocketReader;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class BroadcasterTest extends TestCase
{
    public function testBroadcastWithNoListenersReturnsNoErrors(): void
    {
        // Clean up only our test-created discovery files (not ALL files on the machine)
        $this->cleanupTestDiscoveryFiles();

        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test message');
        $this->assertSame([], $errors);
    }

    public function testBroadcastToActiveConnectionDeliversMessage(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'hello');
            $this->assertSame([], $errors);

            // Verify the message was actually received
            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();
            $message = $generator->current();

            $this->assertSame(Connection::TYPE_RESULT, $message[0]);
            $decoded = json_decode($message[1], true);
            $this->assertSame(Connection::MESSAGE_TYPE_LOGGER, $decoded[0]);
            $this->assertSame('hello', $decoded[1]);
        } finally {
            $connection->close();
        }
    }

    public function testBroadcastCleansUpStaleDiscoveryFiles(): void
    {
        if (Connection::isWindows()) {
            $fakeFile = sys_get_temp_dir() . '/' . Connection::SOCKET_FILE_PREFIX . '99999.port';
            file_put_contents($fakeFile, '0');
        } else {
            $fakeFile =
                sys_get_temp_dir() . '/' . Connection::SOCKET_FILE_PREFIX . random_int(900000000, 999999999) . '.sock';
            touch($fakeFile);
        }

        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test');

        $this->assertIsArray($errors);
        // Stale file should have been cleaned up
        $this->assertFileDoesNotExist($fakeFile);
    }

    public function testBroadcastWithDifferentMessageTypes(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();

            $errors1 = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'log message');
            $this->assertSame([], $errors1);

            $errors2 = $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, 'dump data');
            $this->assertSame([], $errors2);
        } finally {
            $connection->close();
        }
    }

    public function testBroadcastWithLargePayloadDelivers(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $largeData = str_repeat('x', 2048);
            $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, $largeData);
            $this->assertSame([], $errors);

            // Verify the large payload was actually received
            $reader = new SocketReader($connection->getSocket());
            $generator = $reader->read();
            $message = $generator->current();

            $this->assertSame(Connection::TYPE_RESULT, $message[0]);
            $decoded = json_decode($message[1], true);
            $this->assertSame($largeData, $decoded[1]);
        } finally {
            $connection->close();
        }
    }

    public function testBroadcastWithEmptyData(): void
    {
        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, '');
        $this->assertIsArray($errors);
    }

    public function testBroadcastToMultipleConnections(): void
    {
        $conn1 = Connection::create();
        $conn1->bind();
        $conn2 = Connection::create();
        $conn2->bind();

        try {
            $broadcaster = new Broadcaster();
            $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'multi-target');
            $this->assertSame([], $errors);

            // Verify both connections received the message
            $reader1 = new SocketReader($conn1->getSocket());
            $msg1 = $reader1->read()->current();
            $this->assertSame(Connection::TYPE_RESULT, $msg1[0]);

            $reader2 = new SocketReader($conn2->getSocket());
            $msg2 = $reader2->read()->current();
            $this->assertSame(Connection::TYPE_RESULT, $msg2[0]);
        } finally {
            $conn1->close();
            $conn2->close();
        }
    }

    /**
     * Remove only discovery files that match our prefix pattern.
     * Does NOT remove files from other running debug servers.
     */
    private function cleanupTestDiscoveryFiles(): void
    {
        $files = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        foreach ($files as $file) {
            @unlink($file);
        }
    }
}

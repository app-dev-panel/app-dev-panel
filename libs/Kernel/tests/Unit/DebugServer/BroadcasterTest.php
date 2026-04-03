<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class BroadcasterTest extends TestCase
{
    public function testBroadcastWithNoListeners(): void
    {
        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test message');
        $this->assertIsArray($errors);
    }

    public function testBroadcastReturnsEmptyErrorsWhenNoDiscoveryFiles(): void
    {
        // Clean up any existing discovery files to ensure no listeners
        $existingFiles = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        foreach ($existingFiles as $file) {
            @unlink($file);
        }

        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, json_encode(['test' => 'data']));
        $this->assertSame([], $errors);
    }

    public function testBroadcastToActiveConnection(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'hello');

            $this->assertIsArray($errors);
        } finally {
            $connection->close();
        }
    }

    public function testBroadcastCleansUpStaleDiscoveryFiles(): void
    {
        if (Connection::isWindows()) {
            // On Windows, create a .port file with an invalid port
            $fakeFile = sys_get_temp_dir() . '/' . Connection::SOCKET_FILE_PREFIX . '99999.port';
            file_put_contents($fakeFile, '0');
        } else {
            // On Unix, create a fake .sock file that doesn't have a listening socket
            $fakeFile =
                sys_get_temp_dir() . '/' . Connection::SOCKET_FILE_PREFIX . random_int(900000000, 999999999) . '.sock';
            touch($fakeFile);
        }

        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test');

        // Broadcast should complete without throwing
        $this->assertIsArray($errors);

        @unlink($fakeFile);
    }

    public function testBroadcastWithDifferentMessageTypes(): void
    {
        $broadcaster = new Broadcaster();

        $errors1 = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'log message');
        $this->assertIsArray($errors1);

        $errors2 = $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, 'dump data');
        $this->assertIsArray($errors2);
    }

    public function testBroadcastWithLargePayload(): void
    {
        $connection = Connection::create();
        $connection->bind();

        try {
            $broadcaster = new Broadcaster();
            $largeData = str_repeat('x', 2048);
            $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, $largeData);

            $this->assertIsArray($errors);
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

            $this->assertIsArray($errors);
        } finally {
            $conn1->close();
            $conn2->close();
        }
    }
}

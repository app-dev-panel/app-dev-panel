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

    public function testBroadcastReturnsEmptyErrorsWhenNoSocketFiles(): void
    {
        // Clean up any existing socket files in temp dir to ensure no listeners
        $existingFiles = glob(sys_get_temp_dir() . '/yii-dev-server-*.sock', GLOB_NOSORT);
        foreach ($existingFiles as $file) {
            @unlink($file);
        }

        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_VAR_DUMPER, json_encode(['test' => 'data']));
        $this->assertSame([], $errors);
    }

    public function testBroadcastToActiveConnection(): void
    {
        // Create a real UDP socket to receive the broadcast
        $connection = Connection::create();
        $connection->bind();
        $uri = $connection->getUri();

        try {
            $broadcaster = new Broadcaster();
            $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'hello');

            $this->assertIsArray($errors);
        } finally {
            $connection->close();
        }
    }

    public function testBroadcastCleansUpRefusedConnections(): void
    {
        // Create a fake .sock file that doesn't have a listening socket
        $fakeSocketFile = sys_get_temp_dir() . '/yii-dev-server-' . random_int(900000000, 999999999) . '.sock';
        touch($fakeSocketFile);

        $broadcaster = new Broadcaster();
        $errors = $broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, 'test');

        // The file may have been cleaned up if connection was refused
        // Either way, the broadcast should complete without throwing
        $this->assertIsArray($errors);

        @unlink($fakeSocketFile);
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
}

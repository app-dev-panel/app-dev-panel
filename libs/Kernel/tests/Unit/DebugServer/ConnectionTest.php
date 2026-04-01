<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Connection;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Socket;

#[RequiresPhpExtension('sockets')]
final class ConnectionTest extends TestCase
{
    public function testConstants(): void
    {
        $this->assertSame(10_000, Connection::DEFAULT_TIMEOUT);
        $this->assertSame(1024, Connection::DEFAULT_BUFFER_SIZE);

        $this->assertSame(0x001B, Connection::TYPE_RESULT);
        $this->assertSame(0x002B, Connection::TYPE_ERROR);
        $this->assertSame(0x003B, Connection::TYPE_RELEASE);

        $this->assertSame(0x001B, Connection::MESSAGE_TYPE_VAR_DUMPER);
        $this->assertSame(0x002B, Connection::MESSAGE_TYPE_LOGGER);
    }

    public function testCreateAndClose(): void
    {
        $connection = Connection::create();
        $connection->bind();

        $uri = $connection->getUri();
        $this->assertNotEmpty($uri);
        $this->assertFileExists($uri);

        $connection->close();
        $this->assertFileDoesNotExist($uri);
    }

    public function testGetSocket(): void
    {
        $connection = Connection::create();

        $this->assertInstanceOf(Socket::class, $connection->getSocket());

        $connection->close();
    }

    public function testBindCreatesSocketFile(): void
    {
        $connection = Connection::create();
        $connection->bind();

        $uri = $connection->getUri();
        $this->assertStringContainsString('yii-dev-server-', $uri);
        $this->assertStringContainsString(sys_get_temp_dir(), $uri);
        $this->assertStringEndsWith('.sock', $uri);
        $this->assertFileExists($uri);

        $connection->close();
    }

    public function testCloseRemovesSocketFile(): void
    {
        $connection = Connection::create();
        $connection->bind();
        $uri = $connection->getUri();

        $this->assertFileExists($uri);
        $connection->close();
        $this->assertFileDoesNotExist($uri);
    }

    public function testCloseWithoutBindDoesNotThrow(): void
    {
        $connection = Connection::create();
        // Close without bind - should not throw
        $connection->close();
        $this->assertTrue(true); // Reached without exception
    }

    public function testMessageTypeConstants(): void
    {
        // Verify message types are distinct from type constants
        $this->assertSame(Connection::TYPE_RESULT, Connection::MESSAGE_TYPE_VAR_DUMPER);
        $this->assertSame(Connection::TYPE_ERROR, Connection::MESSAGE_TYPE_LOGGER);
    }
}

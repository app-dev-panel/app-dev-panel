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

    public function testSocketFilePrefix(): void
    {
        $this->assertSame('adp-debug-server-', Connection::SOCKET_FILE_PREFIX);
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

    public function testBindCreatesDiscoveryFile(): void
    {
        $connection = Connection::create();
        $connection->bind();

        $uri = $connection->getUri();
        $this->assertStringContainsString(Connection::SOCKET_FILE_PREFIX, $uri);
        $this->assertStringContainsString(sys_get_temp_dir(), $uri);
        $this->assertFileExists($uri);

        if (Connection::isWindows()) {
            $this->assertStringEndsWith('.port', $uri);
            // Port file must contain a valid port number
            $port = (int) file_get_contents($uri);
            $this->assertGreaterThan(0, $port);
            $this->assertLessThanOrEqual(65535, $port);
        } else {
            $this->assertStringEndsWith('.sock', $uri);
        }

        $connection->close();
    }

    public function testCloseRemovesDiscoveryFile(): void
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
        $connection->close();
        $this->assertTrue(true);
    }

    public function testMessageTypeConstants(): void
    {
        $this->assertSame(Connection::TYPE_RESULT, Connection::MESSAGE_TYPE_VAR_DUMPER);
        $this->assertSame(Connection::TYPE_ERROR, Connection::MESSAGE_TYPE_LOGGER);
    }

    public function testMultipleConnectionsHaveUniqueUris(): void
    {
        $conn1 = Connection::create();
        $conn1->bind();
        $conn2 = Connection::create();
        $conn2->bind();

        $this->assertNotSame($conn1->getUri(), $conn2->getUri());

        $conn1->close();
        $conn2->close();
    }

    public function testGetSocketReturnsSameInstance(): void
    {
        $connection = Connection::create();
        $socket1 = $connection->getSocket();
        $socket2 = $connection->getSocket();

        $this->assertSame($socket1, $socket2);

        $connection->close();
    }

    public function testIsWindowsReturnsCorrectValue(): void
    {
        $expected = PHP_OS_FAMILY === 'Windows';
        $this->assertSame($expected, Connection::isWindows());
    }

    public function testDiscoveryPatternContainsPrefix(): void
    {
        $pattern = Connection::discoveryPattern();
        $this->assertStringContainsString(Connection::SOCKET_FILE_PREFIX, $pattern);
        $this->assertStringContainsString(sys_get_temp_dir(), $pattern);

        if (Connection::isWindows()) {
            $this->assertStringEndsWith('*.port', $pattern);
        } else {
            $this->assertStringEndsWith('*.sock', $pattern);
        }
    }

    public function testDiscoveryPatternMatchesBoundConnection(): void
    {
        $connection = Connection::create();
        $connection->bind();
        $uri = $connection->getUri();

        $matches = glob(Connection::discoveryPattern(), GLOB_NOSORT);
        $this->assertContains($uri, $matches);

        $connection->close();
    }

    public function testDoubleCloseDoesNotThrow(): void
    {
        $connection = Connection::create();
        $connection->bind();
        $connection->close();
        // Second close should not throw
        $connection->close();
        $this->assertTrue(true);
    }
}

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
}

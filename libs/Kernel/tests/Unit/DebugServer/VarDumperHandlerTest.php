<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\VarDumperHandler;
use AppDevPanel\Kernel\DumpHandlerInterface;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('sockets')]
final class VarDumperHandlerTest extends TestCase
{
    #[Test]
    public function implementsHandlerInterface(): void
    {
        $handler = new VarDumperHandler();

        $this->assertInstanceOf(DumpHandlerInterface::class, $handler);
        $handler->connection->close();
    }

    #[Test]
    public function connectionPropertyIsPubliclyAccessible(): void
    {
        $handler = new VarDumperHandler();

        $this->assertInstanceOf(Connection::class, $handler->connection);
        $handler->connection->close();
    }
}

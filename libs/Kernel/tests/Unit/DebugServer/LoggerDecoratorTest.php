<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\DebugServer\LoggerDecorator;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[RequiresPhpExtension('sockets')]
final class LoggerDecoratorTest extends TestCase
{
    #[Test]
    public function implementsLoggerInterface(): void
    {
        $decorated = $this->createStub(LoggerInterface::class);
        $decorator = new LoggerDecorator($decorated);

        $this->assertInstanceOf(LoggerInterface::class, $decorator);
        $decorator->connection->close();
    }

    #[Test]
    public function connectionPropertyIsPubliclyAccessible(): void
    {
        $decorated = $this->createStub(LoggerInterface::class);
        $decorator = new LoggerDecorator($decorated);

        $this->assertInstanceOf(Connection::class, $decorator->connection);
        $decorator->connection->close();
    }
}

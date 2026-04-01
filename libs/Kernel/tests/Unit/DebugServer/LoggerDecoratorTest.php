<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\LoggerDecorator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LoggerDecoratorTest extends TestCase
{
    #[Test]
    public function implementsLoggerInterface(): void
    {
        $decorated = $this->createStub(LoggerInterface::class);
        $decorator = new LoggerDecorator($decorated);

        $this->assertInstanceOf(LoggerInterface::class, $decorator);
    }

    #[Test]
    public function broadcasterPropertyIsPubliclyAccessible(): void
    {
        $decorated = $this->createStub(LoggerInterface::class);
        $decorator = new LoggerDecorator($decorated);

        $this->assertInstanceOf(Broadcaster::class, $decorator->broadcaster);
    }

    #[Test]
    public function logForwardsToDecoratedLogger(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())
            ->method('log')
            ->with('info', 'test message', ['key' => 'value']);

        $decorator = new LoggerDecorator($decorated);
        // Broadcaster.broadcast() with no sockets returns [] - safe in tests
        $decorator->log('info', 'test message', ['key' => 'value']);
    }

    #[Test]
    public function logWithEmptyContext(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())
            ->method('log')
            ->with('error', 'error happened', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->log('error', 'error happened');
    }

    #[Test]
    public function logUsesLoggerTraitConvenienceMethods(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())
            ->method('log')
            ->with('warning', 'warn msg', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->warning('warn msg');
    }
}

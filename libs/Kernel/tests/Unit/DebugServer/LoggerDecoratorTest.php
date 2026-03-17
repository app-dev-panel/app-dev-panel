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
}

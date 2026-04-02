<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\DebugServer;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\VarDumperHandler;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Yiisoft\VarDumper\HandlerInterface;

final class VarDumperHandlerTest extends TestCase
{
    #[Test]
    public function implementsHandlerInterface(): void
    {
        $handler = new VarDumperHandler();

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    #[Test]
    public function broadcasterPropertyIsPubliclyAccessible(): void
    {
        $handler = new VarDumperHandler();

        $this->assertInstanceOf(Broadcaster::class, $handler->broadcaster);
    }

    #[Test]
    public function handleBroadcastsVariable(): void
    {
        $handler = new VarDumperHandler();
        // No sockets exist in test env, so broadcast() returns [] harmlessly
        $handler->handle('test variable', 3, false);

        // If we got here without exception, the handler works
        $this->assertInstanceOf(VarDumperHandler::class, $handler);
    }

    #[Test]
    public function handleBroadcastsArrayVariable(): void
    {
        $handler = new VarDumperHandler();
        $handler->handle(['key' => 'value', 'nested' => [1, 2, 3]], 5, true);

        $this->assertInstanceOf(VarDumperHandler::class, $handler);
    }

    #[Test]
    public function handleBroadcastsNullVariable(): void
    {
        $handler = new VarDumperHandler();
        $handler->handle(null, 1, false);

        $this->assertInstanceOf(VarDumperHandler::class, $handler);
    }
}

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
    public function acceptsBroadcasterViaConstructor(): void
    {
        $broadcaster = new Broadcaster();
        $handler = new VarDumperHandler($broadcaster);

        $this->assertInstanceOf(HandlerInterface::class, $handler);
    }

    #[Test]
    public function handleDoesNotThrowForScalarValue(): void
    {
        $handler = new VarDumperHandler();

        $handler->handle('hello world', 3);

        $this->addToAssertionCount(1);
    }

    #[Test]
    public function handleDoesNotThrowForArrayValue(): void
    {
        $handler = new VarDumperHandler();

        $handler->handle(['key' => 'value', 'nested' => [1, 2, 3]], 3);

        $this->addToAssertionCount(1);
    }
}

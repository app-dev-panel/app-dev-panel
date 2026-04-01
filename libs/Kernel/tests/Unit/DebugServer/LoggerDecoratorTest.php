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
        $decorated->expects($this->once())->method('log')->with('info', 'test message', ['key' => 'value']);

        $decorator = new LoggerDecorator($decorated);
        // Broadcaster.broadcast() with no sockets returns [] - safe in tests
        $decorator->log('info', 'test message', ['key' => 'value']);
    }

    #[Test]
    public function logWithEmptyContext(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('error', 'error happened', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->log('error', 'error happened');
    }

    #[Test]
    public function logUsesLoggerTraitConvenienceMethods(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('warning', 'warn msg', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->warning('warn msg');
    }

    #[Test]
    public function emergencyDelegatesToLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('emergency', 'system down', ['code' => 500]);

        $decorator = new LoggerDecorator($decorated);
        $decorator->emergency('system down', ['code' => 500]);
    }

    #[Test]
    public function alertDelegatesToLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('alert', 'alert msg', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->alert('alert msg');
    }

    #[Test]
    public function criticalDelegatesToLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('critical', 'critical failure', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->critical('critical failure');
    }

    #[Test]
    public function noticeDelegatesToLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('notice', 'notice msg', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->notice('notice msg');
    }

    #[Test]
    public function infoDelegatesToLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('info', 'info msg', ['key' => 'val']);

        $decorator = new LoggerDecorator($decorated);
        $decorator->info('info msg', ['key' => 'val']);
    }

    #[Test]
    public function debugDelegatesToLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('debug', 'debug msg', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->debug('debug msg');
    }

    #[Test]
    public function logWithStringableMessage(): void
    {
        $stringable = new class() implements \Stringable {
            public function __toString(): string
            {
                return 'stringable message';
            }
        };

        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('info', $stringable, []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->log('info', $stringable);
    }

    #[Test]
    public function logWithComplexContext(): void
    {
        $context = [
            'exception' => new \RuntimeException('test error'),
            'nested' => ['a' => 1, 'b' => [2, 3]],
            'null_val' => null,
        ];

        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('error', 'complex context', $context);

        $decorator = new LoggerDecorator($decorated);
        $decorator->log('error', 'complex context', $context);
    }

    #[Test]
    public function broadcasterCanBeReplacedBeforeLog(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log');

        $decorator = new LoggerDecorator($decorated);
        $newBroadcaster = new Broadcaster();
        $decorator->broadcaster = $newBroadcaster;

        $this->assertSame($newBroadcaster, $decorator->broadcaster);
        $decorator->log('info', 'after broadcaster replacement');
    }
}

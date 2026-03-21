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
    public function acceptsBroadcasterViaConstructor(): void
    {
        $decorated = $this->createStub(LoggerInterface::class);
        $broadcaster = new Broadcaster();
        $decorator = new LoggerDecorator($decorated, $broadcaster);

        $this->assertInstanceOf(LoggerInterface::class, $decorator);
    }

    #[Test]
    public function delegatesToDecoratedLogger(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('info', 'test message', []);

        $decorator = new LoggerDecorator($decorated);
        $decorator->info('test message');
    }

    #[Test]
    public function passesContextToDecoratedLogger(): void
    {
        $context = ['user' => 'admin', 'action' => 'login'];

        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->once())->method('log')->with('warning', 'access attempt', $context);

        $decorator = new LoggerDecorator($decorated);
        $decorator->warning('access attempt', $context);
    }

    #[Test]
    public function supportsAllPsr3LogLevels(): void
    {
        $decorated = $this->createMock(LoggerInterface::class);
        $decorated->expects($this->exactly(8))->method('log');

        $decorator = new LoggerDecorator($decorated);

        $decorator->emergency('emergency');
        $decorator->alert('alert');
        $decorator->critical('critical');
        $decorator->error('error');
        $decorator->warning('warning');
        $decorator->notice('notice');
        $decorator->info('info');
        $decorator->debug('debug');
    }
}

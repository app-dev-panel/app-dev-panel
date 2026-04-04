<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Proxy;

use AppDevPanel\Adapter\Symfony\Proxy\MessengerCollectorMiddleware;
use AppDevPanel\Kernel\Collector\QueueCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\BusNameStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;

final class MessengerCollectorMiddlewareTest extends TestCase
{
    private QueueCollector $collector;

    protected function setUp(): void
    {
        $this->collector = new QueueCollector(new TimelineCollector());
        $this->collector->startup();
    }

    public function testHandleCollectsDispatchedMessage(): void
    {
        $middleware = new MessengerCollectorMiddleware($this->collector);

        $message = new \stdClass();
        $message->action = 'test';
        $envelope = new Envelope($message);

        // Create a next middleware that returns the envelope with HandledStamp
        $resultEnvelope = $envelope->with(new HandledStamp('result', 'handler'));
        $next = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willReturn($resultEnvelope);

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($next);

        $result = $middleware->handle($envelope, $stack);

        $this->assertSame($resultEnvelope, $result);

        $collected = $this->collector->getCollected();
        $this->assertCount(1, $collected['messages']);
        $this->assertSame('stdClass', $collected['messages'][0]['messageClass']);
        $this->assertTrue($collected['messages'][0]['dispatched']);
        $this->assertTrue($collected['messages'][0]['handled']);
        $this->assertFalse($collected['messages'][0]['failed']);
    }

    public function testHandleCollectsFailedMessage(): void
    {
        $middleware = new MessengerCollectorMiddleware($this->collector);

        $message = new \stdClass();
        $envelope = new Envelope($message);

        $next = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willThrowException(new \RuntimeException('Handler failed'));

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($next);

        $this->expectException(\RuntimeException::class);

        try {
            $middleware->handle($envelope, $stack);
        } finally {
            $collected = $this->collector->getCollected();
            $this->assertCount(1, $collected['messages']);
            $this->assertTrue($collected['messages'][0]['failed']);
        }
    }

    public function testHandleExtractsBusName(): void
    {
        $middleware = new MessengerCollectorMiddleware($this->collector);

        $message = new \stdClass();
        $envelope = new Envelope($message)->with(new BusNameStamp('command.bus'));

        $resultEnvelope = $envelope->with(new HandledStamp('result', 'handler'));
        $next = $this->createMock(MiddlewareInterface::class);
        $next->method('handle')->willReturn($resultEnvelope);

        $stack = $this->createMock(StackInterface::class);
        $stack->method('next')->willReturn($next);

        $middleware->handle($envelope, $stack);

        $collected = $this->collector->getCollected();
        $this->assertSame('command.bus', $collected['messages'][0]['bus']);
    }
}

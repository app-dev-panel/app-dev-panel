<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Tests\Unit\EventListener;

use AppDevPanel\Adapter\Laravel\EventListener\HttpClientListener;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use GuzzleHttp\Psr7\Request as PsrRequest;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use PHPUnit\Framework\TestCase;

final class HttpClientListenerTest extends TestCase
{
    public function testRegistersThreeEventListeners(): void
    {
        $registeredListeners = [];
        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$registeredListeners): void {
                $registeredListeners[$event] = $callback;
            });

        $listener = new HttpClientListener($this->createCollector(...));
        $listener->register($dispatcher);

        $this->assertCount(3, $registeredListeners);
        $this->assertArrayHasKey(RequestSending::class, $registeredListeners);
        $this->assertArrayHasKey(ResponseReceived::class, $registeredListeners);
        $this->assertArrayHasKey(ConnectionFailed::class, $registeredListeners);
    }

    public function testCollectsRequestAndResponse(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $request = new Request(new PsrRequest('GET', 'https://api.example.com/users'));
        $response = new Response(new PsrResponse(200, [], '{"ok":true}'));

        $listeners[RequestSending::class](new RequestSending($request));
        $listeners[ResponseReceived::class](new ResponseReceived($request, $response));

        $collected = $collector->getCollected();
        $this->assertNotEmpty($collected);
    }

    public function testCollectsConnectionFailure(): void
    {
        [$collector, $listeners] = $this->registerListener();

        $request = new Request(new PsrRequest('POST', 'https://api.example.com/timeout'));
        $exception = new ConnectionException('Connection timed out');

        $listeners[RequestSending::class](new RequestSending($request));
        $listeners[ConnectionFailed::class](new ConnectionFailed($request, $exception));

        $collected = $collector->getCollected();
        $this->assertNotEmpty($collected);
    }

    private function createCollector(): HttpClientCollector
    {
        $timeline = new TimelineCollector();
        $collector = new HttpClientCollector($timeline);
        $timeline->startup();
        $collector->startup();
        return $collector;
    }

    /**
     * @return array{HttpClientCollector, array<string, \Closure>}
     */
    private function registerListener(): array
    {
        $collector = $this->createCollector();
        $listeners = [];

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher
            ->method('listen')
            ->willReturnCallback(static function (string $event, \Closure $callback) use (&$listeners): void {
                $listeners[$event] = $callback;
            });

        $listener = new HttpClientListener(static fn() => $collector);
        $listener->register($dispatcher);

        return [$collector, $listeners];
    }
}

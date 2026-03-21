<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\HttpClientCollector;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;

/**
 * Listens for Laravel HTTP client events and feeds the HttpClientCollector.
 *
 * Laravel fires these events from Illuminate\Http\Client\PendingRequest.
 */
final class HttpClientListener
{
    /** @var \Closure(): HttpClientCollector */
    private \Closure $collectorFactory;

    /**
     * @var array<string, float>
     */
    private array $requestStartTimes = [];

    /**
     * @param \Closure(): HttpClientCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function register(Dispatcher $events): void
    {
        $events->listen(RequestSending::class, function (RequestSending $event): void {
            $request = $event->request;
            $uniqueId = spl_object_hash($request);
            $this->requestStartTimes[$uniqueId] = microtime(true);

            ($this->collectorFactory)()->collect(
                $request->toPsrRequest(),
                microtime(true),
                '',
                $uniqueId,
            );
        });

        $events->listen(ResponseReceived::class, function (ResponseReceived $event): void {
            $uniqueId = spl_object_hash($event->request);
            unset($this->requestStartTimes[$uniqueId]);

            ($this->collectorFactory)()->collectTotalTime(
                $event->response->toPsrResponse(),
                microtime(true),
                $uniqueId,
            );
        });

        $events->listen(ConnectionFailed::class, function (ConnectionFailed $event): void {
            $uniqueId = spl_object_hash($event->request);
            unset($this->requestStartTimes[$uniqueId]);

            ($this->collectorFactory)()->collectTotalTime(
                null,
                microtime(true),
                $uniqueId,
            );
        });
    }
}

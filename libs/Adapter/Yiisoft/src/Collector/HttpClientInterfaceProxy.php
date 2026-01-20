<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\Yii\Debug\ProxyDecoratedCalls;

final class HttpClientInterfaceProxy implements ClientInterface
{
    use ProxyDecoratedCalls;

    public function __construct(
        private readonly ClientInterface $decorated,
        private readonly HttpClientCollector $collector
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        /** @psalm-var array{file: string, line: int} $callStack */
        $callStack = debug_backtrace()[0];

        $uniqueId = random_bytes(36);
        $startTime = microtime(true);
        $this->collector->collect($request, $startTime, $callStack['file'] . ':' . $callStack['line'], $uniqueId);

        $response = null;
        try {
            $response = $this->decorated->sendRequest($request);
        } finally {
            $endTime = microtime(true);
            $this->collector->collectTotalTime($response, $endTime, $uniqueId);
        }

        return $response;
    }
}

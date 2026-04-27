<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Middleware;

use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\VarDumper\VarDumper;

/**
 * PSR-15 middleware that maps Spiral's HTTP lifecycle to the ADP Debugger.
 *
 * - Before next handler: Debugger::startup(), RequestCollector, WebAppInfoCollector
 * - After next handler: RequestCollector captures response, adds X-Debug-Id header
 * - Finally: Debugger::shutdown() flushes collectors to storage
 *
 * Skips ADP's own endpoints (`/debug/api`, `/inspect/api`, `/debug`) to avoid recursion.
 */
final class DebugMiddleware implements MiddlewareInterface
{
    private static bool $varDumperHandlerRegistered = false;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly ContainerInterface $container,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isAdpPath($request)) {
            return $handler->handle($request);
        }

        $this->registerVarDumperHandler();
        $this->debugger->startup(StartupContext::forRequest($request));

        $webAppInfo = $this->getOptionalCollector(WebAppInfoCollector::class);
        $requestCollector = $this->getOptionalCollector(RequestCollector::class);
        $exceptionCollector = $this->getOptionalCollector(ExceptionCollector::class);

        if ($webAppInfo instanceof WebAppInfoCollector) {
            $webAppInfo->markApplicationStarted();
            $webAppInfo->markRequestStarted();
        }

        if ($requestCollector instanceof RequestCollector) {
            $requestCollector->collectRequest($request);
        }

        try {
            $response = $handler->handle($request);
        } catch (\Throwable $e) {
            if ($exceptionCollector instanceof ExceptionCollector) {
                $exceptionCollector->collect($e);
            }

            // Build a synthetic 500 response so we can still attach X-Debug-Id — without it,
            // FixtureRunner falls back to "latest" which races under the filemtime 1-second
            // granularity when multiple fixtures fire in the same second.
            $debugId = $this->debugger->getId();
            $body = json_encode([
                'error' => $e::class,
                'message' => $e->getMessage(),
                'debug_id' => $debugId,
            ], JSON_THROW_ON_ERROR) ?: '{}';
            $response = $this->responseFactory
                ->createResponse(500)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-Debug-Id', $debugId)
                ->withBody($this->streamFactory->createStream($body));

            if ($requestCollector instanceof RequestCollector) {
                $requestCollector->collectResponse($response);
            }

            if ($webAppInfo instanceof WebAppInfoCollector) {
                $webAppInfo->markRequestFinished();
                $webAppInfo->markApplicationFinished();
            }

            $this->debugger->shutdown();

            return $response;
        }

        if ($requestCollector instanceof RequestCollector) {
            $requestCollector->collectResponse($response);
        }

        if ($webAppInfo instanceof WebAppInfoCollector) {
            $webAppInfo->markRequestFinished();
            $webAppInfo->markApplicationFinished();
        }

        $response = $response->withHeader('X-Debug-Id', $this->debugger->getId());

        $this->debugger->shutdown();

        return $response;
    }

    private function isAdpPath(ServerRequestInterface $request): bool
    {
        $path = $request->getUri()->getPath();

        return $path === '/debug' || str_starts_with($path, '/debug/') || str_starts_with($path, '/inspect/api');
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T|null
     */
    private function getOptionalCollector(string $class): ?object
    {
        if (!$this->container->has($class)) {
            return null;
        }

        /** @var T */
        return $this->container->get($class);
    }

    private function registerVarDumperHandler(): void
    {
        if (self::$varDumperHandlerRegistered) {
            return;
        }

        $collector = $this->getOptionalCollector(VarDumperCollector::class);
        if (!$collector instanceof VarDumperCollector) {
            return;
        }

        VarDumper::setHandler(static function (mixed $var, ?string $label = null) use ($collector): void {
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
            $line = '';
            foreach ($trace as $frame) {
                if (!(array_key_exists('file', $frame) && !str_contains($frame['file'], 'vendor/'))) {
                    continue;
                }
                $line = $frame['file'] . ':' . ($frame['line'] ?? 0);
                break;
            }
            $collector->collect($var, $label ?? $line);
        });

        self::$varDumperHandlerRegistered = true;
    }
}

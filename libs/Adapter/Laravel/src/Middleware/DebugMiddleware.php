<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Middleware;

use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Illuminate\Http\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\VarDumper\VarDumper;

/**
 * HTTP middleware that maps Laravel request lifecycle to the ADP Debugger.
 *
 * - Before request: Debugger::startup(), RequestCollector, WebAppInfoCollector
 * - After response: RequestCollector captures response, adds X-Debug-Id header
 * - On terminate: Debugger::shutdown()
 */
final class DebugMiddleware
{
    private static bool $varDumperHandlerRegistered = false;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly DebugCollectors $collectors = new DebugCollectors(),
    ) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        if ($this->isAdpApiPath($request)) {
            return $next($request);
        }

        $psrRequest = $this->convertLaravelRequestToPsr7($request);

        $this->registerVarDumperHandler();
        $this->debugger->startup(StartupContext::forRequest($psrRequest));
        $this->collectBeforeRequest($psrRequest);

        try {
            /** @var SymfonyResponse $response */
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->collectors->exception?->collect($e);
            throw $e;
        }

        $this->collectAfterResponse($request, $response);

        $response->headers->set('X-Debug-Id', $this->debugger->getId());

        return $response;
    }

    public function terminate(Request $request, SymfonyResponse $response): void
    {
        if ($this->isAdpApiPath($request)) {
            return;
        }

        $this->collectors->webAppInfo?->markApplicationFinished();
        $this->debugger->shutdown();
    }

    private function isAdpApiPath(Request $request): bool
    {
        $path = $request->getPathInfo();
        return str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api');
    }

    private function collectBeforeRequest(\Psr\Http\Message\ServerRequestInterface $psrRequest): void
    {
        $this->collectors->webAppInfo?->markApplicationStarted();
        $this->collectors->webAppInfo?->markRequestStarted();
        $this->collectors->request?->collectRequest($psrRequest);
        $this->collectors->environment?->collectFromRequest($psrRequest);
    }

    private function collectAfterResponse(Request $request, SymfonyResponse $response): void
    {
        $this->collectors->webAppInfo?->markRequestFinished();

        if ($this->collectors->request !== null) {
            $psrResponse = $this->convertSymfonyResponseToPsr7($response);
            $this->collectors->request->collectResponse($psrResponse);
        }

        $this->collectors->routerDataExtractor?->extract($request);
    }

    private function registerVarDumperHandler(): void
    {
        if (self::$varDumperHandlerRegistered || $this->collectors->varDumper === null) {
            return;
        }

        $collector = $this->collectors->varDumper;
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

    private function convertLaravelRequestToPsr7(Request $request): \Psr\Http\Message\ServerRequestInterface
    {
        $psr17Factory = new Psr17Factory();

        $psrRequest = $psr17Factory->createServerRequest(
            $request->getMethod(),
            $request->getUri(),
            $request->server->all(),
        );

        foreach ($request->headers->all() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        $psrRequest = $psrRequest->withQueryParams($request->query->all());

        $content = $request->getContent();
        if ($content !== '' && $content !== false) {
            $body = $psr17Factory->createStream($content);
            $psrRequest = $psrRequest->withBody($body);
        }

        return $psrRequest;
    }

    private function convertSymfonyResponseToPsr7(SymfonyResponse $response): \Psr\Http\Message\ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        $psrResponse = $psr17Factory->createResponse($response->getStatusCode());

        foreach ($response->headers->all() as $name => $values) {
            $psrResponse = $psrResponse->withHeader($name, $values);
        }

        $content = $response->getContent();
        if ($content !== false) {
            $body = $psr17Factory->createStream($content);
            $psrResponse = $psrResponse->withBody($body);
        }

        return $psrResponse;
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Middleware;

use AppDevPanel\Adapter\Laravel\Collector\RouterDataExtractor;
use AppDevPanel\Kernel\Collector\EnvironmentCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
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
        private readonly ?RequestCollector $requestCollector = null,
        private readonly ?WebAppInfoCollector $webAppInfoCollector = null,
        private readonly ?ExceptionCollector $exceptionCollector = null,
        private readonly ?VarDumperCollector $varDumperCollector = null,
        private readonly ?EnvironmentCollector $environmentCollector = null,
        private readonly ?RouterDataExtractor $routerDataExtractor = null,
    ) {}

    public function handle(Request $request, \Closure $next): mixed
    {
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api')) {
            return $next($request);
        }

        $psrRequest = $this->convertLaravelRequestToPsr7($request);

        $this->registerVarDumperHandler();

        $this->debugger->startup(StartupContext::forRequest($psrRequest));

        $this->webAppInfoCollector?->markApplicationStarted();
        $this->webAppInfoCollector?->markRequestStarted();
        $this->requestCollector?->collectRequest($psrRequest);
        $this->environmentCollector?->collectFromRequest($psrRequest);

        try {
            /** @var SymfonyResponse $response */
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->exceptionCollector?->collect($e);
            throw $e;
        }

        $this->webAppInfoCollector?->markRequestFinished();

        if ($this->requestCollector !== null) {
            $psrResponse = $this->convertSymfonyResponseToPsr7($response);
            $this->requestCollector->collectResponse($psrResponse);
        }

        $this->routerDataExtractor?->extract($request);

        $response->headers->set('X-Debug-Id', $this->debugger->getId());

        return $response;
    }

    public function terminate(Request $request, SymfonyResponse $response): void
    {
        $path = $request->getPathInfo();
        if (str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api')) {
            return;
        }

        $this->webAppInfoCollector?->markApplicationFinished();
        $this->debugger->shutdown();
    }

    private function registerVarDumperHandler(): void
    {
        if (self::$varDumperHandlerRegistered || $this->varDumperCollector === null) {
            return;
        }

        $collector = $this->varDumperCollector;
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

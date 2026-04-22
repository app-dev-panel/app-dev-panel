<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Middleware;

use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use AppDevPanel\Kernel\StartupContext;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
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

    private readonly Psr7Converter $psr7Converter;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly DebugCollectors $collectors = new DebugCollectors(),
        private readonly ?ToolbarInjector $toolbarInjector = null,
    ) {
        $this->psr7Converter = new Psr7Converter();
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        if ($this->isAdpApiPath($request)) {
            return $next($request);
        }

        $psrRequest = $this->psr7Converter->convertRequest($request);

        $this->registerVarDumperHandler();
        $this->debugger->startup(StartupContext::forRequest($psrRequest));
        $this->collectBeforeRequest($psrRequest);

        try {
            /** @var SymfonyResponse $response */
            $response = $next($request);
        } catch (\Throwable $e) {
            $this->collectors->exception?->collect($e);
            if ($e instanceof QueryException) {
                $this->collectors->databaseListener?->collectFailedQuery($e);
            }
            throw $e;
        }

        $this->collectAfterResponse($request, $response);

        $response->headers->set('X-Debug-Id', $this->debugger->getId());

        $this->injectToolbar($request, $response);

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
        if ($this->collectors->request !== null) {
            $psrResponse = $this->psr7Converter->convertResponse($response);
            $this->collectors->request->collectResponse($psrResponse);
        }

        $this->collectors->routerDataExtractor?->extract($request);

        $this->collectViteAssets();

        $this->collectors->webAppInfo?->markRequestFinished();
    }

    private function injectToolbar(Request $request, SymfonyResponse $response): void
    {
        if ($this->toolbarInjector === null || !$this->toolbarInjector->isEnabled()) {
            return;
        }

        if ($this->toolbarInjector->isPanelRequest($request->getPathInfo())) {
            return;
        }

        $contentType = $response->headers->get('Content-Type', '');
        if (!$this->toolbarInjector->isHtmlResponse($contentType)) {
            return;
        }

        $content = $response->getContent();
        if ($content === false || $content === '') {
            return;
        }

        $backendUrl = $request->getSchemeAndHttpHost();
        $injected = $this->toolbarInjector->inject($content, $backendUrl, $this->debugger->getId());
        $response->setContent($injected);
    }

    private function registerVarDumperHandler(): void
    {
        if (self::$varDumperHandlerRegistered || $this->collectors->varDumper === null) {
            return;
        }

        $collector = $this->collectors->varDumper;
        $broadcaster = new Broadcaster();
        VarDumper::setHandler(static function (mixed $var, ?string $label = null) use ($collector, $broadcaster): void {
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

            // Broadcast for Live Feed
            try {
                $broadcaster->broadcast(
                    Connection::MESSAGE_TYPE_VAR_DUMPER,
                    \Yiisoft\VarDumper\VarDumper::create($var)->asJson(false),
                );
            } catch (\Throwable) {
            }
        });

        self::$varDumperHandlerRegistered = true;
    }

    private function collectViteAssets(): void
    {
        if ($this->collectors->viteAssetListener === null || !class_exists(\Illuminate\Foundation\Vite::class)) {
            return;
        }

        $vite = app(\Illuminate\Foundation\Vite::class);
        $this->collectors->viteAssetListener->collect($vite);
    }
}

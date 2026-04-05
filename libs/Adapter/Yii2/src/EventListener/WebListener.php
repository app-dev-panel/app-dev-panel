<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\EventListener;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2RouteCollection;
use AppDevPanel\Adapter\Yii2\Proxy\RouterMatchRecorder;
use AppDevPanel\Api\Toolbar\ToolbarInjector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Nyholm\Psr7\Factory\Psr17Factory;
use yii\web\UrlRule;

/**
 * Maps Yii 2 web application events to the ADP Debugger lifecycle.
 *
 * EVENT_BEFORE_REQUEST -> Debugger::startup() + RequestCollector
 * EVENT_AFTER_REQUEST  -> RequestCollector (captures response) + Debugger::shutdown()
 *
 * Uses Kernel's generic RequestCollector (PSR-7) and ExceptionCollector.
 * Yii 2 uses its own Request/Response objects, converted to PSR-7 via nyholm/psr7.
 */
final class WebListener
{
    private ?Psr17Factory $psr17Factory = null;

    public function __construct(
        private readonly Debugger $debugger,
        private readonly ?RequestCollector $requestCollector = null,
        private readonly ?WebAppInfoCollector $webAppInfoCollector = null,
        private readonly ?ExceptionCollector $exceptionCollector = null,
        private readonly ?RouterCollector $routerCollector = null,
        private readonly ?RouterMatchRecorder $matchRecorder = null,
        private readonly ?ToolbarInjector $toolbarInjector = null,
    ) {}

    public function onBeforeRequest(\yii\base\Event $event): void
    {
        $app = $event->sender;
        if (!$app instanceof \yii\web\Application) {
            return;
        }

        $request = $app->getRequest();
        $path = $request->getUrl();

        // Don't debug ADP's own API requests
        if (str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api')) {
            return;
        }

        $psrRequest = $this->convertYiiRequestToPsr7($request);

        $this->debugger->startup(StartupContext::forRequest($psrRequest));

        $this->webAppInfoCollector?->markApplicationStarted();
        $this->webAppInfoCollector?->markRequestStarted();
        $this->requestCollector?->collectRequest($psrRequest);
    }

    public function onAfterRequest(\yii\base\Event $event): void
    {
        $app = $event->sender;
        if (!$app instanceof \yii\web\Application) {
            return;
        }

        $request = $app->getRequest();
        $path = $request->getUrl();

        if (str_starts_with($path, '/debug/api') || str_starts_with($path, '/inspect/api')) {
            return;
        }

        // Force-flush Yii's Logger so buffered messages reach DebugLogTarget before storage flush.
        // Yii's Logger has flushInterval=1000 by default, so with ~14 messages per request
        // the buffer never auto-flushes. Without this, LogCollector gets 0 messages.
        // Must happen BEFORE markRequestFinished/markApplicationFinished so that DB and log
        // timeline events appear before the finish markers in the timeline.
        \Yii::getLogger()->flush(true);

        $this->extractRouteData($app);

        if ($this->requestCollector !== null) {
            $psrResponse = $this->convertYiiResponseToPsr7($app->getResponse());
            $this->requestCollector->collectResponse($psrResponse);
        }

        // Add debug ID header to the response
        $app->getResponse()->getHeaders()->set('X-Debug-Id', $this->debugger->getId());

        $this->injectToolbar($app);

        $this->webAppInfoCollector?->markRequestFinished();
        $this->webAppInfoCollector?->markApplicationFinished();

        $this->debugger->shutdown();
        $this->matchRecorder?->reset();
    }

    /**
     * Called from the exception handler when EVENT_AFTER_REQUEST won't fire.
     *
     * Extracts route data and flushes logs so the debugger shutdown (via
     * register_shutdown_function) can persist a complete debug entry.
     */
    public function onExceptionHandler(\yii\web\Application $app): void
    {
        \Yii::getLogger()->flush(true);
        $this->extractRouteData($app);
        $this->webAppInfoCollector?->markRequestFinished();
        $this->webAppInfoCollector?->markApplicationFinished();
        $this->matchRecorder?->reset();
    }

    /**
     * Called after each action — captures unhandled exceptions that Yii's error handler caught.
     */
    public function onAfterAction(): void
    {
        $errorHandler = \Yii::$app->getErrorHandler();
        if ($errorHandler->exception !== null) {
            $this->exceptionCollector?->collect($errorHandler->exception);
        }
    }

    private function injectToolbar(\yii\web\Application $app): void
    {
        if ($this->toolbarInjector === null || !$this->toolbarInjector->isEnabled()) {
            return;
        }

        $response = $app->getResponse();

        // Only inject into HTML format responses
        if ($response->format !== \yii\web\Response::FORMAT_HTML) {
            return;
        }

        $content = $response->content;
        if ($content === null || $content === '') {
            return;
        }

        $request = $app->getRequest();
        $backendUrl = $request->getHostInfo();

        $response->content = $this->toolbarInjector->inject($content, $backendUrl, $this->debugger->getId());
    }

    private function getPsr17Factory(): Psr17Factory
    {
        return $this->psr17Factory ??= new Psr17Factory();
    }

    private function convertYiiRequestToPsr7(\yii\web\Request $yiiRequest): \Psr\Http\Message\ServerRequestInterface
    {
        $factory = $this->getPsr17Factory();

        $uri = $factory->createUri($yiiRequest->getAbsoluteUrl());
        $psrRequest = $factory->createServerRequest($yiiRequest->getMethod(), $uri, $_SERVER);

        // Copy headers
        foreach ($yiiRequest->getHeaders() as $name => $values) {
            $psrRequest = $psrRequest->withHeader($name, $values);
        }

        // Copy query params
        $psrRequest = $psrRequest->withQueryParams($yiiRequest->getQueryParams());

        // Copy parsed body
        $bodyParams = $yiiRequest->getBodyParams();
        if (is_array($bodyParams) && $bodyParams !== []) {
            $psrRequest = $psrRequest->withParsedBody($bodyParams);
        }

        return $psrRequest;
    }

    private function convertYiiResponseToPsr7(\yii\web\Response $yiiResponse): \Psr\Http\Message\ResponseInterface
    {
        $factory = $this->getPsr17Factory();
        $psrResponse = $factory->createResponse($yiiResponse->getStatusCode());

        foreach ($yiiResponse->getHeaders() as $name => $values) {
            $psrResponse = $psrResponse->withHeader($name, $values);
        }

        $content = $yiiResponse->content;
        if ($content !== null && $content !== '') {
            $body = $factory->createStream($content);
            $psrResponse = $psrResponse->withBody($body);
        }

        return $psrResponse;
    }

    /**
     * Extract route data from RouterMatchRecorder (proxy-based) or fall back to controller/action.
     *
     * Only collects if the RouterCollector hasn't been fed manually (e.g., by RouterAction fixture).
     * Uses getCollected() emptiness as the check since there's no public "hasRoute" method.
     */
    private function extractRouteData(\yii\web\Application $app): void
    {
        if ($this->routerCollector === null) {
            return;
        }

        // Skip if router data was already collected manually (e.g., by a fixture action)
        $collected = $this->routerCollector->getCollected();
        if (isset($collected['currentRoute'])) {
            return;
        }

        $uri = $app->getRequest()->getUrl();

        // Primary: use proxy-recorded match data (accurate pattern, name, timing)
        if ($this->matchRecorder !== null && $this->matchRecorder->getMatchedRule() !== null) {
            $this->extractFromRecorder($app, $uri);
        } else {
            // Fallback: extract from resolved controller/action (no match timing, no pattern)
            $this->extractFromController($app, $uri);
        }

        // Collect all registered routes for the route list
        $this->collectAllRoutes($app);
    }

    /**
     * Collect route list for the Router panel.
     *
     * When proxy-recorded attempts are available, routes are listed in checking order
     * with a `matched` flag showing which rules matched and which didn't.
     * Falls back to the static route collection from UrlManager.
     */
    private function collectAllRoutes(\yii\web\Application $app): void
    {
        $attempts = $this->matchRecorder?->getAttempts() ?? [];

        if ($attempts !== []) {
            $routes = [];
            foreach ($attempts as $attempt) {
                $rule = $attempt['rule'];
                $routes[] = [
                    'name' => $rule instanceof UrlRule ? $rule->name : $rule::class,
                    'pattern' => $rule instanceof UrlRule ? $rule->name : $rule::class,
                    'methods' => $rule instanceof UrlRule ? ($rule->verb ?? [] ?: ['ANY']) : ['ANY'],
                    'host' => $rule instanceof UrlRule ? $rule->host : null,
                    'matched' => $attempt['matched'],
                ];
            }
            $this->routerCollector->collectRoutes($routes);
            return;
        }

        if (!$app->has('urlManager')) {
            return;
        }

        $urlManager = $app->getUrlManager();
        $collection = new Yii2RouteCollection($urlManager);

        $routes = [];
        foreach ($collection->getRoutes() as $adapter) {
            $info = $adapter->__debugInfo();
            $routes[] = [
                'name' => $info['name'],
                'pattern' => $info['pattern'],
                'methods' => $info['methods'] ?: ['ANY'],
                'host' => $info['hosts'][0] ?? null,
            ];
        }

        $this->routerCollector->collectRoutes($routes);
    }

    /**
     * Extract route data from the proxy-recorded match (preferred path).
     */
    private function extractFromRecorder(\yii\web\Application $app, string $uri): void
    {
        $rule = $this->matchRecorder->getMatchedRule();
        $matchResult = $this->matchRecorder->getMatchResult();
        $controller = $app->controller;

        $name = null;
        $pattern = $uri;
        $host = null;

        if ($rule instanceof UrlRule) {
            $name = $rule->name;
            $pattern = $rule->name;
            $host = $rule->host;
        }

        $action = null;
        if ($controller !== null) {
            $action = $controller->action !== null ? $controller->action::class : $controller::class;
        }

        $this->routerCollector->collectMatchedRoute([
            'matchTime' => $this->matchRecorder->getMatchTime(),
            'name' => $name,
            'pattern' => $pattern,
            'arguments' => $matchResult[1] ?? [],
            'host' => $host,
            'uri' => $uri,
            'action' => $action,
            'middlewares' => [],
        ]);
    }

    /**
     * Fallback: extract route data from Yii2's resolved controller/action.
     *
     * Used when no proxy-recorded match is available (e.g., default route fallback,
     * or when UrlManager rules are not proxied).
     */
    private function extractFromController(\yii\web\Application $app, string $uri): void
    {
        $controller = $app->controller;
        $action = $controller?->action;

        $this->routerCollector->collectMatchedRoute([
            'matchTime' => 0,
            'name' => null,
            'pattern' => $app->requestedRoute ?: $uri,
            'arguments' => $app->requestedParams,
            'host' => null,
            'uri' => $uri,
            'action' => $action !== null ? $action::class : ($controller !== null ? $controller::class : null),
            'middlewares' => [],
        ]);
    }
}

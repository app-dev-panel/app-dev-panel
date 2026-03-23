<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\EventListener;

use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Nyholm\Psr7\Factory\Psr17Factory;

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

        $this->webAppInfoCollector?->markRequestFinished();
        $this->extractRouteData($app);

        if ($this->requestCollector !== null) {
            $psrResponse = $this->convertYiiResponseToPsr7($app->getResponse());
            $this->requestCollector->collectResponse($psrResponse);
        }

        // Add debug ID header to the response
        $app->getResponse()->getHeaders()->set('X-Debug-Id', $this->debugger->getId());

        $this->webAppInfoCollector?->markApplicationFinished();

        // Force-flush Yii's Logger so buffered messages reach DebugLogTarget before storage flush.
        // Yii's Logger has flushInterval=1000 by default, so with ~14 messages per request
        // the buffer never auto-flushes. Without this, LogCollector gets 0 messages.
        \Yii::getLogger()->flush(true);

        $this->debugger->shutdown();
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
     * Auto-extract route data from Yii2's resolved controller/action.
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

        $controller = $app->controller;
        if ($controller === null) {
            return;
        }

        $action = $controller->action;
        $uri = $app->getRequest()->getUrl();
        $path = parse_url($uri, PHP_URL_PATH) ?: $uri;

        $this->routerCollector->collectMatchedRoute([
            'matchTime' => 0,
            'name' => null,
            'pattern' => $path,
            'arguments' => $app->requestedParams,
            'host' => null,
            'uri' => $uri,
            'action' => $action !== null ? $action::class : $controller::class,
            'middlewares' => [],
        ]);
    }
}

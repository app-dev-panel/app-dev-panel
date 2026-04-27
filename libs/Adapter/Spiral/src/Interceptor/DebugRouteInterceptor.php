<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Interceptor;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Spiral\Router\RouteInterface;
use Spiral\Router\Router;

/**
 * Per-route HTTP interceptor that auto-feeds {@see RouterCollector}.
 *
 * Spiral's {@see Router::handle()} stores the matched `RouteInterface`, route name, and
 * matches in PSR-7 request attributes (`Router::ROUTE_ATTRIBUTE` / `ROUTE_NAME` /
 * `ROUTE_MATCHES`). When a route is configured with this interceptor via
 * `RouteInterface::withInterceptors(DebugRouteInterceptor::class)`, ADP receives the
 * matched route metadata without the application having to wire its own router-collector
 * adapter. Match timing is measured around `$handler->handle($context)` and then
 * recorded via {@see RouterCollector::collectMatchTime()}.
 *
 * Opt-in per route — Spiral 3.x has no global router pipeline interceptor hook.
 */
final class DebugRouteInterceptor implements InterceptorInterface
{
    public function __construct(
        private readonly RouterCollector $collector,
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $start = microtime(true);

        $request = $this->extractRequest($context);
        if ($request !== null) {
            $route = $request->getAttribute(Router::ROUTE_ATTRIBUTE);
            $name = $request->getAttribute(Router::ROUTE_NAME);
            $matches = $request->getAttribute(Router::ROUTE_MATCHES) ?? [];

            if ($route instanceof RouteInterface) {
                $pattern = '';
                $uriHandler = $route->getUriHandler();
                if (method_exists($uriHandler, 'getPattern')) {
                    $candidate = $uriHandler->getPattern();
                    if (is_string($candidate)) {
                        $pattern = $candidate;
                    }
                }

                $this->collector->collectMatchedRoute([
                    'matchTime' => 0.0,
                    'name' => is_string($name) ? $name : null,
                    'pattern' => $pattern,
                    'arguments' => is_array($matches) ? $matches : [],
                    'host' => $request->getUri()->getHost(),
                    'uri' => (string) $request->getUri(),
                    'action' => null,
                    'middlewares' => [],
                ]);
            }
        }

        try {
            return $handler->handle($context);
        } finally {
            $this->collector->collectMatchTime(microtime(true) - $start);
        }
    }

    /**
     * The PSR-15 request is exposed as a `request` context attribute by Spiral's
     * router pipeline. Some interceptor pipelines also pass the request as a positional
     * argument under the `request` key — both lookups are tried.
     */
    private function extractRequest(CallContextInterface $context): ?ServerRequestInterface
    {
        $candidate = $context->getAttribute('request');
        if ($candidate instanceof ServerRequestInterface) {
            return $candidate;
        }

        $arguments = $context->getArguments();
        $argRequest = $arguments['request'] ?? null;
        if ($argRequest instanceof ServerRequestInterface) {
            return $argRequest;
        }

        return null;
    }
}

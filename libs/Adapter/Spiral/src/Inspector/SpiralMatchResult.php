<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Inspector;

/**
 * Result of a Spiral URL match, compatible with the duck-typed contract expected by
 * {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::checkRoute()}.
 *
 * `RoutingController` reads `isSuccess()` and (on success) accesses
 * `route()->middlewareDefinitions` (or `middlewares`) via reflection. We expose
 * a `middlewares` public property so reflection finds the middleware list.
 */
final class SpiralMatchResult
{
    /**
     * @var list<string|null>
     */
    public array $middlewares;

    public function __construct(
        private readonly bool $success,
        ?string $controller = null,
    ) {
        $this->middlewares = $controller !== null ? [$controller] : [];
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function route(): self
    {
        return $this;
    }
}

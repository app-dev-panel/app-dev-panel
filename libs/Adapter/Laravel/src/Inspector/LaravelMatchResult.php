<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Inspector;

/**
 * Result of a Laravel URL match, compatible with the interface
 * expected by RoutingController::checkRoute().
 */
final class LaravelMatchResult
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

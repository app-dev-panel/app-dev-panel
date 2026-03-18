<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Inspector;

/**
 * Result of a Symfony URL match, compatible with the interface
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::checkRoute()}.
 */
final class SymfonyMatchResult
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

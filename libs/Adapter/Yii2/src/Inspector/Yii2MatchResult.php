<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Inspector;

/**
 * Result of a Yii 2 URL match, compatible with the interface
 * expected by {@see \AppDevPanel\Api\Inspector\Controller\RoutingController::checkRoute()}.
 */
final class Yii2MatchResult
{
    /**
     * @var list<string>
     */
    public array $middlewares;

    public function __construct(
        private readonly bool $success,
        ?string $route = null,
    ) {
        $this->middlewares = $route !== null ? [$route] : [];
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

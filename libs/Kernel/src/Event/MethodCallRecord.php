<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Event;

/**
 * Immutable record of a proxy method call.
 * Shared between ServiceCollector and ProxyMethodCallEvent to avoid parameter duplication.
 */
final readonly class MethodCallRecord
{
    public function __construct(
        public string $service,
        public string $class,
        public string $methodName,
        public ?array $arguments,
        public mixed $result,
        public string $status,
        public ?object $error,
        public float $timeStart,
        public float $timeEnd,
    ) {}
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class ApiExtensionsConfig
{
    /**
     * @param class-string[] $middlewares Additional middleware class names
     * @param array<string, array<string, class-string>> $commandMap
     */
    public function __construct(
        public readonly array $middlewares = [],
        public readonly array $commandMap = [],
        public readonly array $params = [],
    ) {}
}

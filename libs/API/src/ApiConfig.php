<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class ApiConfig
{
    /**
     * @param string[] $allowedIps
     * @param string[] $allowedHosts
     * @param array<string, array<string, class-string>> $commandMap
     * @param class-string[] $middlewares Additional middleware class names
     */
    public function __construct(
        public readonly bool $enabled = true,
        public readonly array $allowedIps = ['127.0.0.1', '::1'],
        #[\SensitiveParameter]
        public readonly string $authToken = '',
        public readonly array $allowedHosts = [],
        public readonly array $requestReplayAllowedHosts = ['127.0.0.1', 'localhost'],
        public readonly array $middlewares = [],
        public readonly array $commandMap = [],
        public readonly string $storagePath = '',
        public readonly array $params = [],
    ) {}
}

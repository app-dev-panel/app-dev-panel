<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class ApiSecurityConfig
{
    /**
     * @param string[] $allowedIps
     * @param string[] $allowedHosts
     * @param string[] $requestReplayAllowedHosts
     */
    public function __construct(
        public readonly array $allowedIps = ['127.0.0.1', '::1'],
        #[\SensitiveParameter]
        public readonly string $authToken = '',
        public readonly array $allowedHosts = [],
        public readonly array $requestReplayAllowedHosts = ['127.0.0.1', 'localhost'],
    ) {}
}

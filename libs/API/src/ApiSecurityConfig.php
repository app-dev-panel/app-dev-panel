<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class ApiSecurityConfig
{
    /**
     * @param string[] $allowedIps
     * @param string[] $allowedHosts
     * @param string[] $requestReplayAllowedHosts
     * @param bool     $allowDestructiveOperations Controls whether the Inspector
     *                                             exposes endpoints that mutate
     *                                             the host system — running
     *                                             arbitrary commands, installing
     *                                             composer packages, deleting
     *                                             cache entries, executing raw
     *                                             SQL. Off by default: these are
     *                                             RCE-equivalent and should be
     *                                             enabled only after the
     *                                             operator has configured
     *                                             authentication.
     */
    public function __construct(
        public readonly array $allowedIps = ['127.0.0.1', '::1'],
        #[\SensitiveParameter]
        public readonly string $authToken = '',
        public readonly array $allowedHosts = [],
        public readonly array $requestReplayAllowedHosts = ['127.0.0.1', 'localhost'],
        public readonly bool $allowDestructiveOperations = false,
    ) {}
}

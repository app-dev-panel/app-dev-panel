<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class ApiConfig
{
    public function __construct(
        public readonly bool $enabled = true,
        public readonly ApiSecurityConfig $security = new ApiSecurityConfig(),
        public readonly ApiExtensionsConfig $extensions = new ApiExtensionsConfig(),
        public readonly string $storagePath = '',
    ) {}
}

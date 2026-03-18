<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

final class PathResolver implements PathResolverInterface
{
    public function __construct(
        private readonly string $rootPath,
        private readonly string $runtimePath,
    ) {}

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    public function getRuntimePath(): string
    {
        return $this->runtimePath;
    }
}

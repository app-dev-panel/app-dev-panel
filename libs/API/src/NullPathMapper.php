<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

/**
 * No-op path mapper. Returns paths unchanged.
 *
 * Used as default when no path mapping is configured.
 */
final class NullPathMapper implements PathMapperInterface
{
    public function mapToLocal(string $path): string
    {
        return $path;
    }

    public function mapToRemote(string $path): string
    {
        return $path;
    }

    public function getRules(): array
    {
        return [];
    }
}

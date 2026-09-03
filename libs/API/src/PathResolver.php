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

    /**
     * Whether `$path` (after symlink resolution) is `$rootPath` itself or lives beneath it.
     *
     * Both sides go through `realpath()` and the root is compared with a trailing
     * `DIRECTORY_SEPARATOR`, so `/srv/app-backup/.env` is *not* inside `/srv/app`.
     * Non-existent paths are never inside.
     */
    public static function isInside(string $rootPath, string $path): bool
    {
        $root = realpath($rootPath);
        $real = realpath($path);
        if ($root === false || $real === false) {
            return false;
        }

        $rootPrefix = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        return $real === $root || str_starts_with($real . DIRECTORY_SEPARATOR, $rootPrefix);
    }

    /**
     * `realpath()` with the configured string as fallback for non-existent directories,
     * so error paths still produce a 404 instead of a crash.
     */
    public static function canonical(string $path): string
    {
        $real = realpath($path);

        return $real === false ? $path : $real;
    }

    /**
     * Strips a leading `$prefix` (once) from `$path`; used to present root-relative paths.
     */
    public static function stripPrefix(string $prefix, string $path): string
    {
        return str_starts_with($path, $prefix) ? substr($path, strlen($prefix)) : $path;
    }
}

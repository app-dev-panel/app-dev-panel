<?php

declare(strict_types=1);

namespace AppDevPanel\FrontendAssets;

/**
 * Locates the prebuilt ADP panel SPA shipped with this package.
 *
 * The `dist/` directory is produced by `libs/frontend/packages/panel`
 * (Vite build) and is populated by the release pipeline before the
 * package is split into its own repository. When installed via Composer,
 * the assets sit next to this file at `../dist/index.html`.
 */
final class FrontendAssets
{
    public static function path(): string
    {
        return dirname(__DIR__) . '/dist';
    }

    public static function exists(): bool
    {
        return is_file(self::path() . '/index.html');
    }

    /**
     * Resolve the canonical bundle directory for a framework adapter.
     *
     * Prefers this package's `dist/` (release-pinned, contains both panel and
     * toolbar) and falls back to the adapter-local `resources/dist/` so
     * `make build-panel` development workflows keep working inside the
     * monorepo. Returns `null` when neither has a usable bundle.
     */
    public static function resolve(?string $localFallbackDir = null): ?string
    {
        if (self::exists()) {
            return self::path();
        }

        if ($localFallbackDir !== null && is_file($localFallbackDir . '/bundle.js')) {
            return $localFallbackDir;
        }

        return null;
    }
}

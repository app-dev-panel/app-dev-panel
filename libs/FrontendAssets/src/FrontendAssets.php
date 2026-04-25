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
}

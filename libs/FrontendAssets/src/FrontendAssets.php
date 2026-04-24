<?php

declare(strict_types=1);

namespace AppDevPanel\FrontendAssets;

/**
 * Locates the prebuilt ADP panel SPA and toolbar widget shipped with this package.
 *
 * The `dist/` directory is produced by `libs/frontend/packages/panel` and
 * `libs/frontend/packages/toolbar` (Vite builds) and is populated by the
 * release pipeline before the package is split into its own repository.
 * When installed via Composer, the assets sit next to this file at
 * `../dist/index.html` (panel) and `../dist/toolbar/bundle.js` (toolbar).
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

    public static function toolbarPath(): string
    {
        return self::path() . '/toolbar';
    }

    public static function toolbarExists(): bool
    {
        return is_file(self::toolbarPath() . '/bundle.js');
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\FrontendAssets;

/**
 * Locates the prebuilt ADP panel SPA shipped with this package and serves as
 * the single source of truth for the URL prefix, MIME map, and traversal-safe
 * path resolution used by every framework adapter.
 *
 * `dist/` is populated by the split-release pipeline; when installed via
 * Composer the assets sit at `../dist/`.
 */
final class FrontendAssets
{
    /**
     * URL prefix at which adapters mount the panel + toolbar bundle.
     * Adapters (Symfony / Laravel / Yii2 / Yii3) all use this same prefix
     * so panel/toolbar `staticUrl` and the route definitions stay aligned.
     */
    public const URL_PREFIX = '/debug-assets';

    private const MIME_MAP = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'html' => 'text/html; charset=utf-8',
        'svg' => 'image/svg+xml',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'ico' => 'image/x-icon',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'map' => 'application/json; charset=utf-8',
    ];

    /**
     * Vite emits hashed filenames (e.g. `assets/preload-helper-XGcBzeW.js`)
     * which are content-addressed and never change for a given hash.
     * Entry-point files (`bundle.js`, `index.html`, `bundle.css`) re-render on
     * every release with the same name and must NOT be cached aggressively.
     */
    private const HASHED_ASSET_PATTERN = '#-[A-Za-z0-9_]{6,}\.(js|css|woff2?|png|svg|jpg|jpeg|ico|map)$#';

    public static function path(): string
    {
        return dirname(__DIR__) . '/dist';
    }

    public static function exists(): bool
    {
        return is_file(self::path() . '/index.html');
    }

    public static function isAvailable(): bool
    {
        return self::exists();
    }

    /**
     * Resolve a request-relative path inside `dist/` to an absolute file path.
     *
     * Returns `null` when the package is not installed, the file is missing,
     * or the path escapes the `dist/` boundary (directory traversal). Uses
     * `realpath` to normalise both ends, then asserts the resolved path is
     * either equal to `dist/` itself or strictly nested under it.
     */
    public static function resolve(string $relative): ?string
    {
        if (!self::exists()) {
            return null;
        }

        $base = realpath(self::path());
        if ($base === false) {
            return null;
        }

        $resolved = realpath($base . '/' . ltrim($relative, '/'));
        if ($resolved === false) {
            return null;
        }

        $insideBase = $resolved === $base || str_starts_with($resolved, $base . DIRECTORY_SEPARATOR);
        if (!$insideBase || !is_file($resolved)) {
            return null;
        }

        return $resolved;
    }

    public static function mimeFor(string $path): string
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return self::MIME_MAP[$ext] ?? 'application/octet-stream';
    }

    /**
     * `Cache-Control` value for a resolved asset. Hashed Vite chunks get
     * `immutable` + 1y; entry points get a short revalidating window.
     */
    public static function cacheControlFor(string $absolutePath): string
    {
        return preg_match(self::HASHED_ASSET_PATTERN, $absolutePath) === 1
            ? 'public, max-age=31536000, immutable'
            : 'public, max-age=300, must-revalidate';
    }
}

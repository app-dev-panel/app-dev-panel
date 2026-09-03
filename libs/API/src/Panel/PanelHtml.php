<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Panel;

/**
 * Helpers that make the panel SPA mount-agnostic (issue #113).
 *
 * The panel bundle is built with a relative Vite `base` (`./`), so every asset
 * reference inside `index.html` / `bundle.js` resolves against the document
 * base URL. Browsers derive that base from the *page* URL, which breaks as
 * soon as the SPA is served from a route prefix without a trailing slash
 * (`/debug` -> assets requested from `/`) or from a deep link
 * (`/debug/inspector/routes` -> assets requested from `/debug/inspector/`).
 *
 * Every server that emits the panel HTML therefore carries an explicit
 * `<base href="<mount>/">` so `/debug`, `/debug/`, `/debug/inspector/routes`
 * and `/` all resolve `bundle.js`, the service worker, favicons and the
 * manifest from the mount directory.
 */
final class PanelHtml
{
    /**
     * Attribute that marks the `<base>` tag the frontend emits as a placeholder.
     */
    public const string BASE_MARKER = 'data-adp-base';

    /**
     * Normalise a mount path into a `<base href>` value: leading slash,
     * exactly one trailing slash. `''`, `'/'` and `'debug'` become `/`,
     * `/debug/` respectively.
     */
    public static function baseHref(string $mount): string
    {
        $trimmed = trim($mount, '/');

        return $trimmed === '' ? '/' : '/' . $trimmed . '/';
    }

    /**
     * Render the `<base>` element for a mount path.
     */
    public static function baseTag(string $mount): string
    {
        return sprintf(
            '<base href="%s" %s />',
            htmlspecialchars(self::baseHref($mount), ENT_QUOTES, 'UTF-8'),
            self::BASE_MARKER,
        );
    }

    /**
     * Rewrite (or inject) the `<base href>` of a prebuilt `index.html` so it
     * points at the directory the SPA is mounted under.
     *
     * - An existing `<base ...>` tag (the placeholder emitted by the frontend
     *   build) is replaced in place.
     * - Otherwise the tag is inserted right after the opening `<head>` tag.
     * - Documents without a `<head>` are returned untouched.
     */
    public static function injectBaseHref(string $html, string $mount): string
    {
        $tag = self::baseTag($mount);

        $rewritten = preg_replace('~<base\b[^>]*>~i', $tag, $html, 1, $count);
        if ($rewritten !== null && $count > 0) {
            return $rewritten;
        }

        $inserted = preg_replace('~(<head\b[^>]*>)~i', '$1' . $tag, $html, 1, $count);
        if ($inserted !== null && $count > 0) {
            return $inserted;
        }

        return $html;
    }

    /**
     * Resolve the static asset URL against the panel mount.
     *
     * Absolute URLs (`https://cdn...`, `//cdn...`) and root-absolute paths
     * (`/bundles/appdevpanel`) are returned with trailing slashes trimmed.
     * Relative values (`.`, `./`, `assets`) are anchored at the mount
     * directory so `rtrim('./', '/')` can never collapse into a bare `.`
     * that the browser would resolve against the current page URL.
     */
    public static function resolveStaticUrl(string $staticUrl, string $mount): string
    {
        $trimmed = rtrim($staticUrl, '/');

        // Root-absolute (`/`, `/bundles/x`, `//cdn`) or absolute (`https://…`).
        if (str_starts_with($staticUrl, '/') || preg_match('~^[a-z][a-z0-9+.-]*:~i', $trimmed) === 1) {
            return $trimmed;
        }

        $mountRoot = rtrim(self::baseHref($mount), '/');

        if ($trimmed === '' || $trimmed === '.') {
            return $mountRoot;
        }

        if (str_starts_with($trimmed, './')) {
            $trimmed = substr($trimmed, 2);
        }

        return $mountRoot . '/' . $trimmed;
    }
}

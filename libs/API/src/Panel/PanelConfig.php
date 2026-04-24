<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Panel;

/**
 * Configuration for the embedded debug panel (SPA).
 */
final class PanelConfig
{
    public const DEFAULT_STATIC_URL = '/debug/static';
    public const CDN_STATIC_URL = 'https://app-dev-panel.github.io/app-dev-panel';

    public function __construct(
        /**
         * Base URL where the panel's static assets (bundle.js, bundle.css) are served from.
         *
         * Modes:
         * - Local (default): `/debug/static` — served in-process by `AssetsController` from
         *   the bundle shipped by `app-dev-panel/frontend-assets`. Requires the package to be
         *   installed; when missing, the controller returns 404 and the panel stays blank.
         * - CDN: set to `PanelConfig::CDN_STATIC_URL` to load the latest release from GitHub Pages.
         *   Handy for quick demos; fragile when the release on CDN drifts from the installed package.
         * - Custom URL: absolute URL or public path that serves the contents of `panel-dist.tar.gz`.
         */
        public readonly string $staticUrl = self::DEFAULT_STATIC_URL,
        /**
         * The route prefix where the panel is mounted (e.g., '/debug').
         * Used as the React Router basename so client-side routing works.
         */
        public readonly string $viewerBasePath = '/debug',
    ) {}
}

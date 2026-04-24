<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Panel;

/**
 * Configuration for the embedded debug panel (SPA).
 */
final class PanelConfig
{
    public const DEFAULT_STATIC_URL = 'https://app-dev-panel.github.io/app-dev-panel/demo';

    public function __construct(
        /**
         * Base URL where the panel's static assets (bundle.js, bundle.css) are served from.
         *
         * Three modes:
         * - Remote (default): GitHub Pages CDN — always serves the latest release.
         * - Local build: relative path like '/bundles/appdevpanel' (Symfony) or '/vendor/app-dev-panel' (Laravel).
         * - Downloaded release: extract panel-dist.tar.gz to a public directory, point here.
         */
        public readonly string $staticUrl = self::DEFAULT_STATIC_URL,
        /**
         * The route prefix where the panel is mounted (e.g., '/debug').
         * Used as the React Router basename so client-side routing works.
         */
        public readonly string $viewerBasePath = '/debug',
    ) {}
}

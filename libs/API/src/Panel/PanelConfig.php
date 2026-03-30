<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Panel;

/**
 * Configuration for the embedded debug panel (SPA).
 */
final class PanelConfig
{
    public const DEFAULT_STATIC_URL = 'https://app-dev-panel.github.io/app-dev-panel';

    public function __construct(
        /**
         * Base URL where the panel's static assets (bundle.js, bundle.css) are served from.
         * Defaults to the GitHub Pages deployment.
         */
        public readonly string $staticUrl = self::DEFAULT_STATIC_URL,
        /**
         * The route prefix where the panel is mounted (e.g., '/debug').
         * Used as the React Router basename so client-side routing works.
         */
        public readonly string $viewerBasePath = '/debug',
    ) {}
}

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
         * When pointing to a Vite dev server (e.g. http://localhost:3000),
         * the panel auto-detects it and loads with HMR support.
         */
        public readonly string $staticUrl = self::DEFAULT_STATIC_URL,
        /**
         * The route prefix where the panel is mounted (e.g., '/debug').
         * Used as the React Router basename so client-side routing works.
         */
        public readonly string $viewerBasePath = '/debug',
    ) {}

    /**
     * Detect whether staticUrl points to a Vite dev server.
     * Vite dev servers run on localhost/127.0.0.1 with a port.
     */
    public function isDevServer(): bool
    {
        $host = parse_url($this->staticUrl, PHP_URL_HOST);
        $port = parse_url($this->staticUrl, PHP_URL_PORT);

        return $port !== null && ($host === 'localhost' || $host === '127.0.0.1');
    }
}

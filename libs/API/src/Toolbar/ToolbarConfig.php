<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Toolbar;

/**
 * Configuration for the embeddable debug toolbar.
 *
 * The toolbar is a small React widget injected into HTML responses
 * that shows request metrics (time, memory, logs, events, etc.)
 * and provides quick access to the full debug panel.
 */
final class ToolbarConfig
{
    public function __construct(
        /**
         * Whether to inject the toolbar into HTML responses.
         */
        public readonly bool $enabled = true,
        /**
         * Base URL where the toolbar's static assets (bundle.js, bundle.css) are served from.
         *
         * Uses the same URL as PanelConfig::staticUrl by default.
         * In development, set to 'http://localhost:3001' for Vite dev server with HMR.
         */
        public readonly string $staticUrl = '',
    ) {}
}

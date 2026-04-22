<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Toolbar;

use AppDevPanel\Api\Panel\PanelConfig;

/**
 * Injects the ADP debug toolbar widget into HTML responses.
 *
 * Generates and inserts the toolbar HTML snippet (container div, config script,
 * and bundle assets) before the closing </body> tag. Used by framework adapters
 * to embed the toolbar in user application pages.
 */
final class ToolbarInjector
{
    public function __construct(
        private readonly PanelConfig $panelConfig,
        private readonly ToolbarConfig $toolbarConfig = new ToolbarConfig(),
    ) {}

    public function isEnabled(): bool
    {
        return $this->toolbarConfig->enabled;
    }

    /**
     * Check if the response content type indicates HTML.
     */
    public function isHtmlResponse(string $contentType): bool
    {
        return str_contains($contentType, 'text/html');
    }

    /**
     * Check if a request path targets the embedded panel SPA.
     *
     * When the toolbar opens the panel in an iframe, the iframe loads an HTML page
     * served from the panel's base path. Re-injecting the toolbar into that HTML
     * stacks a second toolbar inside the panel itself.
     */
    public function isPanelRequest(string $path): bool
    {
        $panelPath = rtrim($this->panelConfig->viewerBasePath, '/');
        if ($panelPath === '') {
            return false;
        }

        return $path === $panelPath || str_starts_with($path, $panelPath . '/');
    }

    /**
     * Inject toolbar HTML into response body.
     *
     * Inserts the toolbar widget before </body>. If no </body> tag is found,
     * returns the original HTML unchanged.
     */
    public function inject(string $html, string $backendUrl, string $debugId = ''): string
    {
        $pos = strripos($html, '</body>');
        if ($pos === false) {
            return $html;
        }

        $snippet = $this->renderSnippet($backendUrl, $debugId);

        return substr($html, 0, $pos) . $snippet . substr($html, $pos);
    }

    /**
     * Render the toolbar HTML snippet.
     */
    private function renderSnippet(string $backendUrl, string $debugId): string
    {
        $staticUrl = $this->resolveStaticUrl();
        $escapedStaticUrl = htmlspecialchars($staticUrl, ENT_QUOTES, 'UTF-8');
        $jsBackendUrl = addslashes($backendUrl);
        $jsDebugId = addslashes($debugId);
        $jsPanelPath = addslashes(rtrim($this->panelConfig->viewerBasePath, '/'));

        return <<<HTML
            <div id="app-dev-toolbar" style="flex: 1"></div>
            <link rel="stylesheet" href="{$escapedStaticUrl}/toolbar/bundle.css" />
            <script>
                window['__adp_panel_url'] = '{$jsPanelPath}';
                window['AppDevPanelToolbarWidget'] = {
                    config: {
                        containerId: 'app-dev-toolbar',
                        options: {
                            router: { basename: '', useHashRouter: false },
                            backend: {
                                baseUrl: '{$jsBackendUrl}',
                                favoriteUrls: [],
                                usePreferredUrl: true,
                                debugId: '{$jsDebugId}',
                            },
                            panelPath: '{$jsPanelPath}',
                        },
                    },
                };
            </script>
            <script type="module" crossorigin src="{$escapedStaticUrl}/toolbar/bundle.js"></script>
            HTML;
    }

    /**
     * Resolve the static URL for toolbar assets.
     *
     * Priority:
     * 1. ToolbarConfig::staticUrl (explicit toolbar URL, e.g. 'http://localhost:3001' for dev)
     * 2. PanelConfig::staticUrl (shared panel asset URL)
     */
    private function resolveStaticUrl(): string
    {
        $url = $this->toolbarConfig->staticUrl;
        if ($url !== '') {
            return rtrim($url, '/');
        }

        return rtrim($this->panelConfig->staticUrl, '/');
    }
}

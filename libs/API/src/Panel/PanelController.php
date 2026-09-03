<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Panel;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Serves the ADP debug panel as an embedded SPA.
 *
 * Renders a minimal HTML page that loads the panel's bundle.js and bundle.css
 * from a configurable static URL (default: GitHub Pages) and injects the
 * runtime configuration (backend URL, router basename) as a JS variable.
 */
final class PanelController
{
    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly PanelConfig $panelConfig,
    ) {}

    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $basePath = rtrim($this->panelConfig->viewerBasePath, '/');
        // Issue #113: a relative static URL (`.`, `./`, `assets`) must be
        // anchored at the panel mount, otherwise the browser resolves it
        // against the *page* URL and `/debug` loads `/bundle.js` while
        // `/debug/` loads `/debug/bundle.js`.
        $staticUrl = PanelHtml::resolveStaticUrl($this->panelConfig->staticUrl, $basePath);

        // Derive the backend URL from the current request
        $uri = $request->getUri();
        $backendUrl = sprintf('%s://%s', $uri->getScheme(), $uri->getAuthority());

        $html = $this->renderHtml($staticUrl, $basePath, $backendUrl);

        $body = $this->streamFactory->createStream($html);

        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($body);
    }

    private function renderHtml(string $staticUrl, string $basePath, string $backendUrl): string
    {
        $escapedStaticUrl = htmlspecialchars($staticUrl, ENT_QUOTES, 'UTF-8');
        $jsBackendUrl = addslashes($backendUrl);
        $jsBasePath = addslashes($basePath);
        // Issue #113: `<base href="<mount>/">` makes every relative URL in the
        // SPA (service worker registration, lazy chunks, manifest, icons)
        // resolve from the mount directory regardless of whether the page was
        // opened at `/debug`, `/debug/` or a deep link such as
        // `/debug/inspector/routes`.
        $baseTag = PanelHtml::baseTag($basePath);

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="utf-8" />
                {$baseTag}
                <meta name="viewport" content="width=device-width, initial-scale=1" />
                <meta name="description" content="Application Development Panel" />
                <meta http-equiv="Permissions-Policy" content="interest-cohort=()" />
                <link rel="icon" href="{$escapedStaticUrl}/favicon.ico" />
                <link rel="icon" type="image/png" sizes="32x32" href="{$escapedStaticUrl}/favicon-32x32.png" />
                <link rel="icon" type="image/png" sizes="16x16" href="{$escapedStaticUrl}/favicon-16x16.png" />
                <link rel="apple-touch-icon" sizes="192x192" href="{$escapedStaticUrl}/android-chrome-192x192.png" />
                <meta name="apple-mobile-web-app-capable" content="yes" />
                <meta name="apple-mobile-web-app-title" content="App Dev Panel" />
                <meta name="application-name" content="App Dev Panel" />
                <meta name="msapplication-TileColor" content="#2563EB" />
                <meta name="theme-color" content="#2563EB" />
                <title>App Dev Panel</title>
                <link rel="stylesheet" href="{$escapedStaticUrl}/bundle.css" />
            </head>
            <body style="display: flex; flex-direction: column; min-height: 100vh; justify-content: space-between">
                <noscript>You need to enable JavaScript to run this app.</noscript>
                <div id="root" style="flex: 1"></div>
                <script>
                    window['__adp_panel_url'] = '{$jsBasePath}';
                    window['AppDevPanelWidget'] = {
                        config: {
                            containerId: 'root',
                            options: {
                                modules: { toolbar: true },
                                router: { basename: '{$jsBasePath}', useHashRouter: false },
                                backend: {
                                    baseUrl: '{$jsBackendUrl}',
                                    favoriteUrls: [],
                                    usePreferredUrl: true,
                                },
                            },
                        },
                    };
                </script>
                <script type="module" crossorigin src="{$escapedStaticUrl}/bundle.js"></script>
            </body>
            </html>
            HTML;
    }
}

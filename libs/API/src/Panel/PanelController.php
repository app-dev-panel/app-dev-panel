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
        $staticUrl = rtrim($this->panelConfig->staticUrl, '/');
        $basePath = rtrim($this->panelConfig->viewerBasePath, '/');

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

        return <<<HTML
            <!doctype html>
            <html lang="en">
            <head>
                <meta charset="utf-8" />
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

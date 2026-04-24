<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Controller;

use AppDevPanel\FrontendAssets\FrontendAssets;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Streams prebuilt panel SPA + toolbar assets shipped by `app-dev-panel/frontend-assets`.
 *
 * Mounted at `/debug-assets/{path}` by the adapter's route file and referenced from
 * `PanelConfig::$staticUrl` / `ToolbarConfig::$staticUrl` when the FrontendAssets
 * package is installed and populated. Removes the runtime dependency on
 * `app-dev-panel.github.io` for the bundle.
 */
final class AdpAssetController
{
    public function __invoke(Request $request, string $path): Response
    {
        if (!class_exists(FrontendAssets::class) || !FrontendAssets::exists()) {
            throw new NotFoundHttpException('Frontend assets package is not installed.');
        }

        $base = realpath(FrontendAssets::path());
        if ($base === false) {
            throw new NotFoundHttpException('Frontend assets directory missing.');
        }

        $requested = $base . '/' . ltrim($path, '/');
        $resolved = realpath($requested);

        // Prevent directory traversal: resolved path must stay inside dist/.
        if ($resolved === false || !str_starts_with($resolved, $base . DIRECTORY_SEPARATOR) && $resolved !== $base) {
            throw new NotFoundHttpException(sprintf('Asset "%s" not found.', $path));
        }

        if (!is_file($resolved)) {
            throw new NotFoundHttpException(sprintf('Asset "%s" not found.', $path));
        }

        $response = new BinaryFileResponse($resolved);
        $response->setPublic();
        $response->setMaxAge(3600);
        $response->headers->set('Content-Type', $this->mimeFor($resolved));

        return $response;
    }

    private function mimeFor(string $file): string
    {
        return match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'js', 'mjs' => 'application/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'html' => 'text/html; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'map' => 'application/json; charset=utf-8',
            default => 'application/octet-stream',
        };
    }
}

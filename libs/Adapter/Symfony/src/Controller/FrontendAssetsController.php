<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Controller;

use AppDevPanel\FrontendAssets\FrontendAssets;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Serves prebuilt panel + toolbar static files from the `app-dev-panel/frontend-assets`
 * Composer package under `/bundles/appdevpanel/{file}`.
 *
 * The URL prefix matches Symfony's historical `assets:install` convention, so installations
 * that already copied the bundle to `public/bundles/appdevpanel/` via the webserver keep
 * working unchanged — the webserver serves files directly via `try_files`, and this
 * controller fires only when the bundle is installed through Composer instead.
 */
final class FrontendAssetsController
{
    public function __invoke(string $file): Response
    {
        if (!FrontendAssets::exists()) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $root = FrontendAssets::path();
        $realRoot = realpath($root);
        $candidate = realpath($root . '/' . $file);

        if ($realRoot === false || $candidate === false) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if (!str_starts_with($candidate, $realRoot . DIRECTORY_SEPARATOR) && $candidate !== $realRoot) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        if (!is_file($candidate)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $response = new BinaryFileResponse($candidate);
        $response->headers->set('Content-Type', $this->contentTypeFor($candidate));
        $response->headers->set('Cache-Control', 'public, max-age=31536000, immutable');

        return $response;
    }

    private function contentTypeFor(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'js', 'mjs' => 'application/javascript; charset=utf-8',
            'css' => 'text/css; charset=utf-8',
            'html' => 'text/html; charset=utf-8',
            'json' => 'application/json; charset=utf-8',
            'svg' => 'image/svg+xml',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'ico' => 'image/x-icon',
            'woff' => 'font/woff',
            'woff2' => 'font/woff2',
            'ttf' => 'font/ttf',
            'map' => 'application/json; charset=utf-8',
            default => 'application/octet-stream',
        };
    }
}

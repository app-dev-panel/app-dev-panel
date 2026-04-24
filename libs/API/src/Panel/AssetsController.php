<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Panel;

use GuzzleHttp\Psr7\MimeType;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Serves the prebuilt panel SPA assets (bundle.js, bundle.css, chunks, favicons, toolbar bundle)
 * from the directory returned by `AppDevPanel\FrontendAssets\FrontendAssets::path()`.
 *
 * Resolved via `class_exists` to keep the `api` module a leaf w.r.t. `frontend-assets` — the
 * dependency is declared at the adapter level, not inside the API module itself. When the
 * package is absent, requests to `/debug/static/*` return 404 and the panel falls back to
 * whatever URL is configured in `PanelConfig::$staticUrl` (typically a CDN).
 */
final class AssetsController
{
    /**
     * Overrides for text-like extensions — Guzzle's MimeType table returns these without a charset,
     * which makes browsers guess encoding. Everything else (images, fonts) goes through Guzzle.
     */
    private const CHARSET_OVERRIDES = [
        'js' => 'application/javascript; charset=utf-8',
        'mjs' => 'application/javascript; charset=utf-8',
        'css' => 'text/css; charset=utf-8',
        'html' => 'text/html; charset=utf-8',
        'json' => 'application/json; charset=utf-8',
        'map' => 'application/json; charset=utf-8',
        'txt' => 'text/plain; charset=utf-8',
    ];

    private ?string $resolvedRoot = null;
    private bool $rootResolved = false;

    public function __construct(
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly StreamFactoryInterface $streamFactory,
        /**
         * Absolute filesystem path of the `dist/` directory. `null` asks the controller to
         * discover it via `AppDevPanel\FrontendAssets\FrontendAssets::path()` at request time.
         * Adapters that already know the path (e.g. from DI) can pass it directly.
         */
        private readonly ?string $assetsRoot = null,
    ) {}

    public function serve(ServerRequestInterface $request): ResponseInterface
    {
        $root = $this->resolveAssetsRoot();
        if ($root === null) {
            return $this->notFound('Frontend assets package is not installed.');
        }

        $path = (string) $request->getAttribute('path', '');
        $path = ltrim($path, '/');
        if ($path === '') {
            return $this->notFound('Asset path is empty.');
        }

        $real = realpath($root . '/' . $path);
        if ($real === false || !is_file($real) || !str_starts_with($real, $root . DIRECTORY_SEPARATOR)) {
            return $this->notFound('Asset not found.');
        }

        $extension = strtolower(pathinfo($real, PATHINFO_EXTENSION));

        return $this->responseFactory
            ->createResponse(200)
            ->withHeader('Content-Type', $this->resolveMime($extension))
            ->withHeader('Cache-Control', $this->resolveCacheControl($path))
            ->withBody($this->streamFactory->createStreamFromFile($real, 'rb'));
    }

    private function resolveMime(string $extension): string
    {
        return self::CHARSET_OVERRIDES[$extension] ?? MimeType::fromExtension($extension) ?? 'application/octet-stream';
    }

    /**
     * Vite emits hashed filenames under `assets/` and `toolbar/assets/`; those are safe to mark
     * `immutable` with a long max-age. Stable names like `bundle.js` must be revalidated so that
     * `composer update app-dev-panel/frontend-assets` isn't masked by a year-long cache.
     */
    private function resolveCacheControl(string $path): string
    {
        if (preg_match('#(^|/)assets/#', $path) === 1) {
            return 'public, max-age=31536000, immutable';
        }

        return 'public, max-age=3600, must-revalidate';
    }

    /**
     * Resolve the filesystem root that holds the panel dist, or null when the
     * `app-dev-panel/frontend-assets` package is not installed and no explicit path was given.
     * The resolution is memoised — the package directory never moves at runtime.
     */
    private function resolveAssetsRoot(): ?string
    {
        if ($this->rootResolved) {
            return $this->resolvedRoot;
        }

        $this->resolvedRoot = $this->discoverAssetsRoot();
        $this->rootResolved = true;

        return $this->resolvedRoot;
    }

    private function discoverAssetsRoot(): ?string
    {
        if ($this->assetsRoot !== null) {
            $real = realpath($this->assetsRoot);

            return $real !== false && is_dir($real) ? $real : null;
        }

        $class = 'AppDevPanel\\FrontendAssets\\FrontendAssets';
        if (!class_exists($class)) {
            return null;
        }

        /** @var callable $pathFn */
        $pathFn = [$class, 'path'];
        $real = realpath((string) $pathFn());

        return $real !== false && is_dir($real) ? $real : null;
    }

    private function notFound(string $message): ResponseInterface
    {
        return $this->responseFactory
            ->createResponse(404)
            ->withHeader('Content-Type', 'text/plain; charset=utf-8')
            ->withBody($this->streamFactory->createStream($message));
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Controller;

use AppDevPanel\FrontendAssets\FrontendAssets;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Streams the prebuilt panel + toolbar bundles shipped by
 * `app-dev-panel/frontend-assets` directly from the Composer vendor directory.
 *
 * Lets Symfony apps use the panel out of the box after `composer require
 * app-dev-panel/adapter-symfony` — no `bin/console assets:install` step
 * required, no manual symlink. For prod-grade setups where static assets are
 * served by nginx from `public/`, see `AssetsInstallCommand` which prebakes
 * the same files into `public/bundles/appdevpanel/`.
 *
 * Path-traversal defence: the requested file's realpath must be a descendant
 * of `$baseDir` — anything else returns 404.
 */
final class AdpAssetsController
{
    public function __construct(
        private readonly string $baseDir = '',
    ) {}

    public function __invoke(string $path): Response
    {
        $baseDir = $this->baseDir !== '' ? $this->baseDir : FrontendAssets::path();

        $base = realpath($baseDir);
        if ($base === false) {
            throw new NotFoundHttpException('ADP frontend assets are not installed.');
        }

        $target = realpath($base . DIRECTORY_SEPARATOR . $path);
        if ($target === false) {
            throw new NotFoundHttpException(sprintf('ADP asset not found: %s', $path));
        }

        if ($target !== $base && !str_starts_with($target, $base . DIRECTORY_SEPARATOR)) {
            throw new NotFoundHttpException(sprintf('ADP asset not found: %s', $path));
        }

        if (!is_file($target)) {
            throw new NotFoundHttpException(sprintf('ADP asset not found: %s', $path));
        }

        $response = new BinaryFileResponse($target);
        $response->setAutoEtag();
        $response->setAutoLastModified();

        return $response;
    }
}

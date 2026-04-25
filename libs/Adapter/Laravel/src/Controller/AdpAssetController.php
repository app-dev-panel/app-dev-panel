<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\Controller;

use AppDevPanel\FrontendAssets\FrontendAssets;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Laravel mirror of the Symfony AdpAssetController — streams panel + toolbar
 * bundles from `app-dev-panel/frontend-assets` over `/debug-assets/{path}`.
 */
final class AdpAssetController
{
    public function __invoke(string $path = ''): Response
    {
        $resolved = FrontendAssets::resolve($path);
        if ($resolved === null) {
            throw new NotFoundHttpException(sprintf('Asset "%s" not found.', $path));
        }

        $response = new BinaryFileResponse($resolved);
        $response->headers->set('Content-Type', FrontendAssets::mimeFor($resolved));
        $response->headers->set('Cache-Control', FrontendAssets::cacheControlFor($resolved));

        return $response;
    }
}

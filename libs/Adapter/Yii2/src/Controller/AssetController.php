<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\FrontendAssets\FrontendAssets;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

/**
 * Streams panel + toolbar bundles from `app-dev-panel/frontend-assets`
 * over `/debug-assets/{path}`. Mirrors the Symfony / Laravel controllers.
 */
final class AssetController extends Controller
{
    public $enableCsrfValidation = false;

    public function actionHandle(string $path = ''): Response
    {
        $resolved = FrontendAssets::resolve($path);
        if ($resolved === null) {
            throw new NotFoundHttpException(sprintf('Asset "%s" not found.', $path));
        }

        $response = \Yii::$app->getResponse();
        $response->getHeaders()->set('Content-Type', FrontendAssets::mimeFor($resolved))->set(
            'Cache-Control',
            FrontendAssets::cacheControlFor($resolved),
        );

        return $response->sendFile($resolved, basename($resolved), [
            'inline' => true,
            'mimeType' => FrontendAssets::mimeFor($resolved),
        ]);
    }
}

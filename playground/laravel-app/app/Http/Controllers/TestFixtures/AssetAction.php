<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use Illuminate\Http\JsonResponse;

final readonly class AssetAction
{
    public function __construct(
        private AssetBundleCollector $assetCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->assetCollector->collectBundles([
            '/build/app.js' => [
                'class' => 'Vite',
                'sourcePath' => 'resources/js/app.js',
                'basePath' => null,
                'baseUrl' => '/build/app.js',
                'css' => [],
                'js' => ['/build/app.js'],
                'depends' => ['/build/vendor.js'],
                'options' => [],
            ],
            '/build/vendor.js' => [
                'class' => 'Vite',
                'sourcePath' => null,
                'basePath' => null,
                'baseUrl' => '/build/vendor.js',
                'css' => [],
                'js' => ['/build/vendor.js'],
                'depends' => [],
                'options' => [],
            ],
            '/build/app.css' => [
                'class' => 'Vite',
                'sourcePath' => 'resources/css/app.css',
                'basePath' => null,
                'baseUrl' => '/build/app.css',
                'css' => ['/build/app.css'],
                'js' => [],
                'depends' => [],
                'options' => [],
            ],
        ]);

        return new JsonResponse(['fixture' => 'assets:basic', 'status' => 'ok']);
    }
}

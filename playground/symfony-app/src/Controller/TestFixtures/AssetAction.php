<?php

declare(strict_types=1);

namespace App\Controller\TestFixtures;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Asset fixture — calls the collector directly because symfony/asset-mapper is not
 * installed in this playground. When AssetMapper is available, the AssetMapperSubscriber
 * automatically feeds asset data to the collector. This fixture simulates that data path.
 */
#[Route('/test/fixtures/assets', name: 'test_assets', methods: ['GET'])]
final readonly class AssetAction
{
    public function __construct(
        private AssetBundleCollector $assetCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->assetCollector->collectBundles([
            'app.js' => [
                'class' => 'AssetMapper',
                'sourcePath' => '/assets/app.js',
                'basePath' => null,
                'baseUrl' => '/assets/app.abc123.js',
                'css' => [],
                'js' => ['app.js'],
                'depends' => ['vendor.js'],
                'options' => [],
            ],
            'vendor.js' => [
                'class' => 'AssetMapper',
                'sourcePath' => '/assets/vendor.js',
                'basePath' => null,
                'baseUrl' => '/assets/vendor.def456.js',
                'css' => [],
                'js' => ['vendor.js'],
                'depends' => [],
                'options' => [],
            ],
            'styles.css' => [
                'class' => 'AssetMapper',
                'sourcePath' => '/assets/styles.css',
                'basePath' => null,
                'baseUrl' => '/assets/styles.ghi789.css',
                'css' => ['styles.css'],
                'js' => [],
                'depends' => [],
                'options' => [],
            ],
        ]);

        return new JsonResponse(['fixture' => 'assets:basic', 'status' => 'ok']);
    }
}

<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class AssetAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private AssetBundleCollector $assetCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->assetCollector->collectBundles([
            'App\\Assets\\MainAsset' => [
                'class' => 'App\\Assets\\MainAsset',
                'sourcePath' => '@assets',
                'basePath' => '/assets',
                'baseUrl' => '/assets',
                'css' => ['css/app.css'],
                'js' => ['js/app.js'],
                'depends' => ['App\\Assets\\BootstrapAsset'],
                'options' => [],
            ],
            'App\\Assets\\BootstrapAsset' => [
                'class' => 'App\\Assets\\BootstrapAsset',
                'sourcePath' => '@npm/bootstrap/dist',
                'basePath' => '/assets',
                'baseUrl' => '/assets',
                'css' => ['css/bootstrap.min.css'],
                'js' => ['js/bootstrap.bundle.min.js'],
                'depends' => [],
                'options' => [],
            ],
            'App\\Assets\\JqueryAsset' => [
                'class' => 'App\\Assets\\JqueryAsset',
                'sourcePath' => '@npm/jquery/dist',
                'basePath' => '/assets',
                'baseUrl' => '/assets',
                'css' => [],
                'js' => ['jquery.min.js'],
                'depends' => [],
                'options' => ['jsOptions' => ['position' => 'head']],
            ],
        ]);

        return $this->responseFactory->createResponse(['fixture' => 'assets:basic', 'status' => 'ok']);
    }
}

<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use App\Web\Shared\Layout\Main\MainAsset;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Assets\AssetLoaderInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class AssetAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private AssetLoaderInterface $assetLoader,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Load real asset bundles via yiisoft/assets — the AssetLoaderInterfaceProxy
        // intercepts loadBundle() calls and feeds bundle data to AssetBundleCollector.
        $bundle = $this->assetLoader->loadBundle(MainAsset::class);

        return $this->responseFactory->createResponse([
            'fixture' => 'assets:basic',
            'status' => 'ok',
            'bundle' => $bundle::class,
        ]);
    }
}

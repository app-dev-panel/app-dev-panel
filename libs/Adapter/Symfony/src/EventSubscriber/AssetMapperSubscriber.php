<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\EventSubscriber;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Collects mapped assets from Symfony's AssetMapper at the end of a request.
 *
 * Only active when symfony/asset-mapper is installed and configured.
 */
final class AssetMapperSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly AssetBundleCollector $collector,
        private readonly AssetMapperInterface $assetMapper,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => ['onKernelResponse', -512],
        ];
    }

    public function onKernelResponse(): void
    {
        $bundles = [];

        foreach ($this->assetMapper->allAssets() as $asset) {
            $cssFiles = [];
            $jsFiles = [];

            if (str_ends_with($asset->logicalPath, '.css')) {
                $cssFiles[] = $asset->logicalPath;
            } elseif (str_ends_with($asset->logicalPath, '.js')) {
                $jsFiles[] = $asset->logicalPath;
            }

            $depends = [];
            foreach ($asset->getDependencies() as $dependency) {
                $depends[] = $dependency->asset->logicalPath;
            }

            $bundles[$asset->logicalPath] = [
                'class' => 'AssetMapper',
                'sourcePath' => $asset->sourcePath,
                'basePath' => null,
                'baseUrl' => $asset->publicPath,
                'css' => $cssFiles,
                'js' => $jsFiles,
                'depends' => $depends,
                'options' => [],
            ];
        }

        if ($bundles !== []) {
            $this->collector->collectBundles($bundles);
        }
    }
}

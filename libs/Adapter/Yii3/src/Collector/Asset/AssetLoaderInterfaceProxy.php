<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Collector\Asset;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use Yiisoft\Assets\AssetBundle;
use Yiisoft\Assets\AssetLoaderInterface;

/**
 * Decorates AssetLoaderInterface to capture loaded asset bundles
 * into the framework-agnostic AssetBundleCollector.
 */
final class AssetLoaderInterfaceProxy implements AssetLoaderInterface
{
    public function __construct(
        private readonly AssetLoaderInterface $decorated,
        private readonly AssetBundleCollector $collector,
    ) {}

    public function loadBundle(string $name, array $config = []): AssetBundle
    {
        $bundle = $this->decorated->loadBundle($name, $config);

        $this->collector->collectBundle($name, [
            'class' => $bundle::class,
            'sourcePath' => $bundle->sourcePath,
            'basePath' => $bundle->basePath,
            'baseUrl' => $bundle->baseUrl,
            'css' => $bundle->css,
            'js' => $bundle->js,
            'depends' => $bundle->depends,
            'options' => array_filter([
                'cssOptions' => $bundle->cssOptions,
                'jsOptions' => $bundle->jsOptions,
                'publishOptions' => $bundle->publishOptions,
            ]),
        ]);

        return $bundle;
    }

    public function getAssetUrl(AssetBundle $bundle, string $assetPath): string
    {
        return $this->decorated->getAssetUrl($bundle, $assetPath);
    }
}

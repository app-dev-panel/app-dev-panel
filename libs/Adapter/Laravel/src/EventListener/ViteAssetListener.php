<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Laravel\EventListener;

use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use Illuminate\Foundation\Vite;

/**
 * Collects rendered Vite assets after the response is created.
 *
 * Reads preloaded assets from Laravel's Vite singleton to capture
 * which JS/CSS entries were rendered during the request.
 */
final class ViteAssetListener
{
    /** @var \Closure(): AssetBundleCollector */
    private \Closure $collectorFactory;

    /**
     * @param \Closure(): AssetBundleCollector $collectorFactory
     */
    public function __construct(\Closure $collectorFactory)
    {
        $this->collectorFactory = $collectorFactory;
    }

    public function collect(Vite $vite): void
    {
        $preloaded = $vite->preloadedAssets();
        if ($preloaded === []) {
            return;
        }

        $collector = ($this->collectorFactory)();
        $bundles = [];

        foreach ($preloaded as $url => $attributes) {
            $type = $attributes['as'] ?? 'unknown';
            $cssFiles = [];
            $jsFiles = [];

            if ($type === 'style') {
                $cssFiles[] = $url;
            } elseif ($type === 'script') {
                $jsFiles[] = $url;
            }

            $bundles[$url] = [
                'class' => 'Vite',
                'sourcePath' => null,
                'basePath' => null,
                'baseUrl' => $url,
                'css' => $cssFiles,
                'js' => $jsFiles,
                'depends' => [],
                'options' => array_filter($attributes),
            ];
        }

        $collector->collectBundles($bundles);
    }
}

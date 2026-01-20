<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

interface ModuleFederationProviderInterface extends CollectorInterface
{
    /**
     * Returns the asset bundle that will be registered when collector data is requested.
     * Example:
     * ```php
     * public static function getAsset(): string
     * {
     *     return new DebugAsset()
     * }
     * ```
     */
    public static function getAsset(): ModuleFederationAssetBundle;
}

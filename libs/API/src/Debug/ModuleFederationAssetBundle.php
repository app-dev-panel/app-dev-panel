<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug;

if (!class_exists(\Yiisoft\Assets\AssetBundle::class)) {
    class AssetBundle
    {
    }
} else {
    class_alias(\Yiisoft\Assets\AssetBundle::class, AssetBundle::class);
}

abstract class ModuleFederationAssetBundle extends AssetBundle
{
    /**
     * The module name is defined into the webpack module federation config file.
     * Example: "remote"
     */
    abstract public static function getModule(): string;

    /**
     * The scope is defined into the webpack module federation config file.
     * Scope is usually the name of the exposed component.
     * Example: "./MyPanel"
     */
    abstract public static function getScope(): string;
}

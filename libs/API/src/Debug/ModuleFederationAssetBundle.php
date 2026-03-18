<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug;

abstract class ModuleFederationAssetBundle
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

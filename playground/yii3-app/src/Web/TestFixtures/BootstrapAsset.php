<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Yiisoft\Assets\AssetBundle;

final class BootstrapAsset extends AssetBundle
{
    public ?string $basePath = '@assets/bootstrap';
    public ?string $baseUrl = '@assetsUrl/bootstrap';
    public ?string $sourcePath = '@assetsSource/bootstrap';

    public array $css = [
        'bootstrap.min.css',
    ];

    public array $js = [
        'bootstrap.bundle.min.js',
    ];
}

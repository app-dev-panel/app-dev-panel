<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Yiisoft\Assets\AssetBundle;

final class JqueryAsset extends AssetBundle
{
    public ?string $basePath = '@assets/jquery';
    public ?string $baseUrl = '@assetsUrl/jquery';
    public ?string $sourcePath = '@assetsSource/jquery';

    public array $js = [
        'jquery.min.js',
    ];
}

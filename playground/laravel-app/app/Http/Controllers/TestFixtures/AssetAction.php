<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use Illuminate\Http\Response;

/**
 * Renders a Blade view with @vite() directive.
 * The ViteAssetListener (called by DebugMiddleware) reads preloadedAssets()
 * from Laravel's Vite singleton and feeds AssetBundleCollector.
 */
final class AssetAction
{
    public function __invoke(): Response
    {
        return response(view('test.assets')->render());
    }
}

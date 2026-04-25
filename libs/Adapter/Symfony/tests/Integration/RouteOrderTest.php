<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\Loader\PhpFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Verifies the adapter's route file registers all ADP routes in the correct order.
 *
 * Specifically, `/debug-assets/*` must resolve to `adp_assets` rather than the
 * panel catch-all `adp_panel` (which matches `/debug/{path}` and would swallow
 * asset requests if registered first).
 */
final class RouteOrderTest extends TestCase
{
    private UrlMatcher $matcher;

    protected function setUp(): void
    {
        $collection = $this->loadAdapterRoutes();
        $this->matcher = new UrlMatcher($collection, new RequestContext());
    }

    public function testDebugAssetsMatchesAdpAssets(): void
    {
        $match = $this->matcher->match('/debug-assets/bundle.js');
        $this->assertSame('adp_assets', $match['_route']);
    }

    public function testDebugAssetsToolbarSubpathMatchesAdpAssets(): void
    {
        $match = $this->matcher->match('/debug-assets/toolbar/bundle.js');
        $this->assertSame('adp_assets', $match['_route']);
    }

    public function testDebugApiMatchesAdpDebugApi(): void
    {
        $match = $this->matcher->match('/debug/api/settings');
        $this->assertSame('adp_debug_api', $match['_route']);
    }

    public function testPanelSubpathMatchesAdpPanel(): void
    {
        $match = $this->matcher->match('/debug/list');
        $this->assertSame('adp_panel', $match['_route']);
    }

    public function testPanelRootMatchesAdpPanel(): void
    {
        // Both adp_panel (/debug/{path}, path defaults to '') and adp_panel_root (/debug)
        // match /debug; the first registered (adp_panel) wins, which is fine since both
        // hit AdpApiController.
        $match = $this->matcher->match('/debug');
        $this->assertSame('adp_panel', $match['_route']);
    }

    private function loadAdapterRoutes(): RouteCollection
    {
        $path = \dirname(__DIR__, 2) . '/config/routes/adp.php';
        $loader = new PhpFileLoader(new FileLocator(\dirname($path)));
        $loader->setResolver(new LoaderResolver([$loader]));

        $collection = new RouteCollection();
        $configurator = new RoutingConfigurator($collection, $loader, $path, $path);

        $callback = require $path;
        $callback($configurator);

        return $collection;
    }
}

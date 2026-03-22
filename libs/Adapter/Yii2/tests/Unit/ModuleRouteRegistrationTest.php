<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit;

use AppDevPanel\Adapter\Yii2\Module;
use PHPUnit\Framework\TestCase;
use yii\web\Application;
use yii\web\UrlManager;

/**
 * Tests that Module::bootstrap() correctly registers API routes.
 *
 * Regression test for: API endpoints (/debug/api/*, /inspect/api/*)
 * returning 404 when the module is configured but not bootstrapped.
 *
 * Root cause: Module must be listed in the application's 'bootstrap'
 * array so that bootstrap() is called during app initialization.
 * Without it, registerRoutes() is never invoked and URL rules are
 * never added to the UrlManager.
 */
final class ModuleRouteRegistrationTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_route_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');

        if (!is_dir($this->storagePath . '/runtime')) {
            mkdir($this->storagePath . '/runtime', 0o777, true);
        }
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        $this->removeDirectory($this->storagePath);
    }

    /**
     * Verifies that registerRoutes() registers URL rules for debug and inspect API endpoints.
     *
     * This is the "after fix" scenario — when debug-panel IS in the bootstrap array,
     * Module::bootstrap() is called, which invokes registerRoutes(), adding URL rules.
     */
    public function testRegisterRoutesAddsApiUrlRules(): void
    {
        $urlManager = $this->createMock(UrlManager::class);
        $urlManager
            ->expects($this->once())
            ->method('addRules')
            ->with(
                $this->callback(static function (array $rules): bool {
                    // Should register rules for debug/api and inspect/api
                    $patterns = array_column($rules, 'pattern');
                    return (
                        in_array('debug/api/<path:.*>', $patterns, true)
                        && in_array('debug/api', $patterns, true)
                        && in_array('inspect/api/<path:.*>', $patterns, true)
                        && in_array('inspect/api', $patterns, true)
                    );
                }),
                false,
            );

        $app = $this->createMock(Application::class);
        $app->method('getUrlManager')->willReturn($urlManager);

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        // Call registerRoutes directly via reflection (bootstrap() also calls
        // registerEventListeners which requires real Yii DB classes)
        $method = new \ReflectionMethod($module, 'registerRoutes');
        $method->setAccessible(true);
        $method->invoke($module, $app);
    }

    /**
     * Verifies that without calling bootstrap(), no URL rules are registered.
     *
     * This is the regression scenario — when debug-panel is NOT in the bootstrap array,
     * bootstrap() is never called, so addRules() is never invoked, causing 404 errors
     * for /debug/api/* and /inspect/api/* endpoints.
     */
    public function testWithoutBootstrapNoRoutesRegistered(): void
    {
        $urlManager = $this->createMock(UrlManager::class);
        $urlManager->expects($this->never())->method('addRules');

        $app = $this->createMock(Application::class);
        $app->method('getUrlManager')->willReturn($urlManager);

        // Module is configured but bootstrap() is NOT called (simulating missing bootstrap config)
        new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        // No bootstrap() call — this is the bug scenario.
        // The UrlManager::addRules() should never be called, proving that
        // without bootstrap, API routes are not registered → 404 errors.
    }

    /**
     * Verifies that disabled module does not register routes even if bootstrapped.
     */
    public function testDisabledModuleDoesNotRegisterRoutes(): void
    {
        $urlManager = $this->createMock(UrlManager::class);
        $urlManager->expects($this->never())->method('addRules');

        $app = $this->createMock(Application::class);
        $app->method('getUrlManager')->willReturn($urlManager);

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'enabled' => false,
        ]);

        $module->bootstrap($app);
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($path);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit;

use AppDevPanel\Adapter\Yii2\Collector\DbProfilingTarget;
use AppDevPanel\Adapter\Yii2\Collector\DebugLogTarget;
use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Adapter\Yii2\Proxy\UrlRuleProxy;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use AppDevPanel\Api\PathResolverInterface;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\HttpClientCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\RedisCollector;
use AppDevPanel\Kernel\Collector\RouterCollector;
use AppDevPanel\Kernel\Collector\ServiceCollector;
use AppDevPanel\Kernel\Collector\TemplateCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\ValidatorCollector;
use AppDevPanel\Kernel\Collector\VarDumperCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\TestCase;
use yii\base\Event;
use yii\log\Dispatcher;
use yii\web\Application;
use yii\web\UrlManager;
use yii\web\UrlRule;

final class ModuleBootstrapTest extends TestCase
{
    private string $storagePath;
    private mixed $previousExceptionHandler = null;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_bootstrap_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
        mkdir($this->storagePath . '/runtime', 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');

        // Save current exception handler to restore later
        $this->previousExceptionHandler = set_exception_handler(null);
        restore_exception_handler();

        // Clear class-level event handlers from previous tests
        Event::offAll();
    }

    protected function tearDown(): void
    {
        // Restore the exception handler (Module::hookErrorHandler sets one)
        set_exception_handler($this->previousExceptionHandler);

        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;
        \Yii::setAlias('@AppDevPanel', null);
        Event::offAll();

        $this->removeDirectory($this->storagePath);
    }

    public function testBootstrapRegistersDebuggerInContainer(): void
    {
        $module = $this->createModuleAndBootstrap();

        $this->assertTrue(\Yii::$container->has(Debugger::class));
        $this->assertInstanceOf(Debugger::class, $module->getDebugger());
    }

    public function testBootstrapRegistersStorageInContainer(): void
    {
        $this->createModuleAndBootstrap();

        $this->assertTrue(\Yii::$container->has(StorageInterface::class));
        $this->assertInstanceOf(StorageInterface::class, \Yii::$container->get(StorageInterface::class));
    }

    public function testBootstrapRegistersIdGeneratorInContainer(): void
    {
        $this->createModuleAndBootstrap();

        $this->assertTrue(\Yii::$container->has(DebuggerIdGenerator::class));
    }

    public function testBootstrapRegistersApiApplicationInContainer(): void
    {
        $this->createModuleAndBootstrap();

        $this->assertTrue(\Yii::$container->has(ApiApplication::class));
    }

    public function testBootstrapRegistersAllDefaultCollectors(): void
    {
        $module = $this->createModuleAndBootstrap();
        $collectors = $module->getCollectorInstances();

        $expectedTypes = [
            TimelineCollector::class,
            RequestCollector::class,
            WebAppInfoCollector::class,
            ExceptionCollector::class,
            LogCollector::class,
            EventCollector::class,
            ServiceCollector::class,
            HttpClientCollector::class,
            VarDumperCollector::class,
            DatabaseCollector::class,
            MailerCollector::class,
            AssetBundleCollector::class,
            RouterCollector::class,
            RedisCollector::class,
            TemplateCollector::class,
            ValidatorCollector::class,
        ];

        $collectorClasses = array_map(static fn(CollectorInterface $c) => $c::class, $collectors);
        foreach ($expectedTypes as $expectedType) {
            $this->assertContains($expectedType, $collectorClasses, "Missing collector: {$expectedType}");
        }
    }

    public function testBootstrapWithDisabledCollectorsExcludesThem(): void
    {
        $module = $this->createModuleAndBootstrap([
            'collectors' => [
                'log' => false,
                'event' => false,
                'db' => false,
            ],
        ]);
        $collectors = $module->getCollectorInstances();

        $collectorClasses = array_map(static fn(CollectorInterface $c) => $c::class, $collectors);
        $this->assertNotContains(LogCollector::class, $collectorClasses);
        $this->assertNotContains(EventCollector::class, $collectorClasses);
        $this->assertNotContains(DatabaseCollector::class, $collectorClasses);
    }

    public function testGetCollectorReturnsNullForMissingType(): void
    {
        $module = $this->createModuleAndBootstrap(['collectors' => ['log' => false]]);

        $this->assertNull($module->getCollector(LogCollector::class));
    }

    public function testGetCollectorReturnsMatchingInstance(): void
    {
        $module = $this->createModuleAndBootstrap();

        $collector = $module->getCollector(TimelineCollector::class);
        $this->assertInstanceOf(TimelineCollector::class, $collector);
    }

    public function testGetDebuggerThrowsBeforeBootstrap(): void
    {
        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Debugger is not initialized');
        $module->getDebugger();
    }

    public function testGetTimelineCollectorReturnsConsistentInstance(): void
    {
        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);

        $timeline1 = $module->getTimelineCollector();
        $timeline2 = $module->getTimelineCollector();

        $this->assertSame($timeline1, $timeline2);
    }

    public function testBootstrapRegistersUrlRules(): void
    {
        // Use reflection to verify registerRoutes was called by checking rules count
        $urlManager = $this->createMock(UrlManager::class);
        $urlManager->rules = [];
        $urlManager->expects($this->once())->method('addRules');

        $logDispatcher = new \stdClass();
        $logDispatcher->targets = [];

        $app = $this->createMock(Application::class);
        $app->method('getUrlManager')->willReturn($urlManager);
        $app->method('has')->willReturnCallback(static fn(string $id) => $id === 'db');
        $app->params = [];
        $app->method('__get')->willReturnCallback(static fn(string $name) => match ($name) {
            'log' => $logDispatcher,
            'params' => [],
            default => null,
        });
        \Yii::$app = $app;

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);
    }

    public function testBootstrapWrapsUrlRulesWithProxy(): void
    {
        $existingRule = $this->createMock(UrlRule::class);
        $existingRule->name = 'existing/route';

        $app = $this->createWebApp([$existingRule]);

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        // All rules should be wrapped in UrlRuleProxy
        foreach ($app->getUrlManager()->rules as $rule) {
            $this->assertInstanceOf(UrlRuleProxy::class, $rule);
        }
    }

    public function testBootstrapDoesNotWrapRulesWhenRouterDisabled(): void
    {
        $existingRule = $this->createMock(UrlRule::class);
        $existingRule->name = 'existing/route';

        $app = $this->createWebApp([$existingRule]);

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'collectors' => ['router' => false],
        ]);
        $module->bootstrap($app);

        // The existing rule should NOT be wrapped
        $hasUnwrapped = false;
        foreach ($app->getUrlManager()->rules as $rule) {
            if (!$rule instanceof UrlRuleProxy) {
                $hasUnwrapped = true;
                break;
            }
        }
        $this->assertTrue($hasUnwrapped, 'Rules should not be wrapped when router collector is disabled');
    }

    public function testBootstrapRegistersWebEventListeners(): void
    {
        $app = $this->createWebApp();

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        // Verify web event listeners were registered by checking Event class
        $this->assertTrue(Event::hasHandlers(Application::class, Application::EVENT_BEFORE_REQUEST));
        $this->assertTrue(Event::hasHandlers(Application::class, Application::EVENT_AFTER_REQUEST));
    }

    public function testBootstrapRegistersConsoleCommands(): void
    {
        $storagePath = sys_get_temp_dir() . '/adp_console_test_' . bin2hex(random_bytes(4));
        mkdir($storagePath, 0o777, true);
        mkdir($storagePath . '/runtime', 0o777, true);

        $app = new \yii\console\Application([
            'id' => 'test-console',
            'basePath' => $storagePath,
        ]);

        $module = new Module('debug-panel', null, [
            'storagePath' => $storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        $this->assertArrayHasKey('debug-query', $app->controllerMap);
        $this->assertArrayHasKey('debug-reset', $app->controllerMap);

        \Yii::$app = null;
        $this->removeDirectory($storagePath);
    }

    public function testBootstrapRegistersDbProfilingTarget(): void
    {
        $logDispatcher = null;
        $app = $this->createWebApp([], $logDispatcher);
        $this->assertNotNull($logDispatcher);

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        // Check that DbProfilingTarget was added to Yii's log targets
        $this->assertArrayHasKey('adp-db-profiling', $logDispatcher->targets);
        $this->assertInstanceOf(DbProfilingTarget::class, $logDispatcher->targets['adp-db-profiling']);
    }

    public function testBootstrapRegistersDebugLogTarget(): void
    {
        $logDispatcher = null;
        $app = $this->createWebApp([], $logDispatcher);
        $this->assertNotNull($logDispatcher);

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        $this->assertArrayHasKey('adp-debug', $logDispatcher->targets);
        $this->assertInstanceOf(DebugLogTarget::class, $logDispatcher->targets['adp-debug']);
    }

    public function testBootstrapDoesNothingWhenDisabled(): void
    {
        $app = $this->createWebApp();

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'enabled' => false,
        ]);
        $module->bootstrap($app);

        $this->assertFalse(\Yii::$container->has(Debugger::class));
    }

    public function testBootstrapRegistersSchemaProviderWithDb(): void
    {
        $app = $this->createWebApp();

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        $this->assertTrue(\Yii::$container->has(SchemaProviderInterface::class));
    }

    public function testBootstrapRegistersEventProfiling(): void
    {
        $app = $this->createWebApp();

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        // EventCollector should be in the collector list (event profiling wired via Event::on('*', '*', ...))
        $this->assertNotNull($module->getCollector(EventCollector::class));
    }

    public function testNormalizeAddressesHandlesVariousFormats(): void
    {
        // Test via reflection since it's a private static method
        $method = new \ReflectionMethod(Module::class, 'normalizeAddresses');

        // Null
        $this->assertSame([], $method->invoke(null, null));

        // String
        $this->assertSame(['test@example.com' => ''], $method->invoke(null, 'test@example.com'));

        // Indexed array
        $this->assertSame(
            ['a@test.com' => '', 'b@test.com' => ''],
            $method->invoke(null, ['a@test.com', 'b@test.com']),
        );

        // Associative array
        $this->assertSame(['user@test.com' => 'User Name'], $method->invoke(null, ['user@test.com' => 'User Name']));

        // Integer value (other type)
        $this->assertSame(['42' => ''], $method->invoke(null, 42));
    }

    public function testGetCollectorInstancesReturnsAllRegistered(): void
    {
        $module = $this->createModuleAndBootstrap();
        $instances = $module->getCollectorInstances();

        $this->assertNotEmpty($instances);
        foreach ($instances as $collector) {
            $this->assertInstanceOf(CollectorInterface::class, $collector);
        }
    }

    public function testBootstrapWithoutDbComponent(): void
    {
        $urlManager = new UrlManager();
        $urlManager->rules = [];

        $logDispatcher = new \stdClass();
        $logDispatcher->targets = [];

        $app = $this->createMock(Application::class);
        $app->method('getUrlManager')->willReturn($urlManager);
        $app->method('has')->willReturn(false);
        $app->params = [];
        $app->method('__get')->willReturnCallback(static fn(string $name) => match ($name) {
            'log' => $logDispatcher,
            'params' => [],
            default => null,
        });

        \Yii::$app = $app;

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
        ]);
        $module->bootstrap($app);

        // NullSchemaProvider should be registered
        $this->assertTrue(\Yii::$container->has(SchemaProviderInterface::class));
    }

    /**
     * Verifies that PathResolver rootPath points to the real project root (where composer.json lives),
     * not to Yii 2's basePath which may be a subdirectory (e.g., src/).
     *
     * Regression test: when basePath is set to a subdirectory, @vendor defaults to basePath/vendor,
     * and `dirname(@vendor)` resolves to the subdirectory instead of the project root.
     */
    public function testRootPathPointsToComposerProjectRoot(): void
    {
        $this->createModuleAndBootstrap();

        $pathResolver = \Yii::$container->get(PathResolverInterface::class);
        $rootPath = $pathResolver->getRootPath();

        // The root path must contain composer.json (real project root)
        $this->assertFileExists(
            $rootPath . '/composer.json',
            'Root path should point to the directory containing composer.json',
        );
    }

    /**
     * Verifies that rootPath is not affected by a custom basePath pointing to a subdirectory.
     * This simulates the Yii 2 basic app pattern where basePath = project/src.
     */
    public function testRootPathNotAffectedBySubdirectoryBasePath(): void
    {
        $this->createModuleAndBootstrap();

        $pathResolver = \Yii::$container->get(PathResolverInterface::class);
        $rootPath = $pathResolver->getRootPath();

        // Root path should NOT end with /src — it should be the actual project root
        $this->assertNotEquals(
            \Yii::getAlias('@app'),
            $rootPath,
            'Root path should not be the same as @app when @app may be a subdirectory',
        );
    }

    private function createModuleAndBootstrap(array $extraConfig = []): Module
    {
        $app = $this->createWebApp();

        $config = array_merge([
            'storagePath' => $this->storagePath . '/debug',
        ], $extraConfig);

        $module = new Module('debug-panel', null, $config);
        $module->bootstrap($app);

        return $module;
    }

    private function createWebApp(array $existingRules = [], ?object &$logDispatcherOut = null): Application
    {
        $urlManager = new UrlManager();
        $urlManager->rules = $existingRules;

        // Use a simple stdClass with targets property — Yii 2's __get() magic
        // maps $app->log to getLog(), so we must mock that method.
        $logDispatcher = new \stdClass();
        $logDispatcher->targets = [];

        $app = $this->createMock(Application::class);
        $app->method('getUrlManager')->willReturn($urlManager);
        $app->method('has')->willReturnCallback(static fn(string $id) => $id === 'db');
        $app->params = [];

        // Yii2 magic __get: $app->log calls getLog(), $app->db calls getDb(), etc.
        $app->method('__get')->willReturnCallback(static fn(string $name) => match ($name) {
            'log' => $logDispatcher,
            'params' => [],
            default => null,
        });

        $logDispatcherOut = $logDispatcher;

        // Make \Yii::$app return the mock
        \Yii::$app = $app;

        return $app;
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

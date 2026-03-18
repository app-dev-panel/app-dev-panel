<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Collector\AssetBundleCollector;
use AppDevPanel\Adapter\Yii2\Collector\DbCollector;
use AppDevPanel\Adapter\Yii2\Collector\MailerCollector;
use AppDevPanel\Adapter\Yii2\Inspector\Yii2ConfigProvider;
use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for the Yii 2 ADP Module.
 *
 * Tests the full bootstrap lifecycle without a real Yii application.
 * Verifies that all services, collectors, and the debugger are properly wired.
 */
#[CoversNothing]
final class ModuleIntegrationTest extends TestCase
{
    private string $storagePath;
    private ?Module $module = null;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_yii2_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);

        // Ensure Yii container is clean
        if (class_exists(\Yii::class)) {
            \Yii::$container = new \yii\di\Container();
        }
    }

    protected function tearDown(): void
    {
        $this->module = null;

        if (class_exists(\Yii::class)) {
            \Yii::$container = new \yii\di\Container();
        }

        $this->removeDirectory($this->storagePath);
    }

    public function testModuleRegistersAllDefaultCollectors(): void
    {
        $module = $this->createModule();

        $collectors = $module->getCollectorInstances();
        $this->assertNotEmpty($collectors);

        $collectorClasses = array_map(static fn ($c) => $c::class, $collectors);

        $expectedClasses = [
            TimelineCollector::class,
            RequestCollector::class,
            WebAppInfoCollector::class,
            ExceptionCollector::class,
            LogCollector::class,
            EventCollector::class,
            DbCollector::class,
            MailerCollector::class,
            AssetBundleCollector::class,
        ];

        foreach ($expectedClasses as $expected) {
            $this->assertContains($expected, $collectorClasses, "Missing collector: $expected");
        }
    }

    public function testModuleBuildsDebugger(): void
    {
        $module = $this->createModule();

        $debugger = $module->getDebugger();
        $this->assertInstanceOf(Debugger::class, $debugger);
    }

    public function testModuleRegistersStorageInContainer(): void
    {
        $this->createModule();

        $this->assertTrue(\Yii::$container->has(StorageInterface::class));
        $this->assertTrue(\Yii::$container->has(DebuggerIdGenerator::class));
        $this->assertTrue(\Yii::$container->has(Debugger::class));
    }

    public function testModuleRegistersApiApplicationInContainer(): void
    {
        $this->createModule();

        $this->assertTrue(\Yii::$container->has(ApiApplication::class));
    }

    public function testGetCollectorByClass(): void
    {
        $module = $this->createModule();

        $dbCollector = $module->getCollector(DbCollector::class);
        $this->assertInstanceOf(DbCollector::class, $dbCollector);

        $timelineCollector = $module->getCollector(TimelineCollector::class);
        $this->assertInstanceOf(TimelineCollector::class, $timelineCollector);

        $this->assertNull($module->getCollector(\stdClass::class));
    }

    public function testSelectiveCollectorDisabling(): void
    {
        $module = $this->createModule([
            'db' => false,
        ]);

        $collectorClasses = array_map(static fn ($c) => $c::class, $module->getCollectorInstances());

        $this->assertNotContains(DbCollector::class, $collectorClasses);
        $this->assertContains(LogCollector::class, $collectorClasses);
        $this->assertContains(RequestCollector::class, $collectorClasses);
    }

    public function testTimelineCollectorIsSingleton(): void
    {
        $module = $this->createModule();

        $timeline1 = $module->getTimelineCollector();
        $timeline2 = $module->getTimelineCollector();

        $this->assertSame($timeline1, $timeline2);
    }

    public function testDebuggerLifecycleStartupShutdown(): void
    {
        $module = $this->createModule();
        $debugger = $module->getDebugger();

        // Simulate startup
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/test');

        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        // Collect some data
        /** @var DbCollector $dbCollector */
        $dbCollector = $module->getCollector(DbCollector::class);
        $dbCollector->logQuery('SELECT 1', [], 1);

        // Shutdown — flush to storage
        $debugger->shutdown();

        // Verify data was stored
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries, 'Storage should contain at least one summary entry after flush');
    }

    private function createModule(array $collectorOverrides = []): Module
    {
        // We need a minimal Yii app for aliases
        if (!\Yii::$app) {
            // Set up minimal Yii environment
            \Yii::setAlias('@app', $this->storagePath);
            \Yii::setAlias('@runtime', $this->storagePath . '/runtime');

            if (!is_dir($this->storagePath . '/runtime')) {
                mkdir($this->storagePath . '/runtime', 0o777, true);
            }
        }

        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'historySize' => 10,
        ]);

        if ($collectorOverrides !== []) {
            $module->collectors = array_merge($module->collectors, $collectorOverrides);
        }

        // Manually call the parts of bootstrap that don't require a real Application
        $this->invokePrivateMethod($module, 'registerServices', [\Yii::$app]);
        $this->invokePrivateMethod($module, 'registerCollectors');
        $this->invokePrivateMethod($module, 'buildDebugger');

        $this->module = $module;
        return $module;
    }

    private function invokePrivateMethod(object $object, string $method, array $args = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $method);
        $reflection->setAccessible(true);
        return $reflection->invoke($object, ...$args);
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

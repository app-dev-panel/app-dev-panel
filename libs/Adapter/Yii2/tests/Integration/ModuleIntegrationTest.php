<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Api\ApiApplication;
use AppDevPanel\Kernel\Collector\AssetBundleCollector;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\EventCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\MailerCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\StartupContext;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration test for the Yii 2 ADP Module.
 *
 * Tests the full bootstrap lifecycle using a real Yii 2 Application.
 * Verifies that all services, collectors, and the debugger are properly wired.
 */
#[CoversNothing]
final class ModuleIntegrationTest extends Yii2IntegrationTestCase
{
    public function testModuleRegistersAllDefaultCollectors(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();

        $collectors = $module->getCollectorInstances();
        $this->assertNotEmpty($collectors);

        $collectorClasses = array_map(static fn($c) => $c::class, $collectors);

        $expectedClasses = [
            TimelineCollector::class,
            RequestCollector::class,
            WebAppInfoCollector::class,
            ExceptionCollector::class,
            LogCollector::class,
            EventCollector::class,
            DatabaseCollector::class,
            MailerCollector::class,
            AssetBundleCollector::class,
        ];

        foreach ($expectedClasses as $expected) {
            $this->assertContains($expected, $collectorClasses, "Missing collector: {$expected}");
        }
    }

    public function testModuleBuildsDebugger(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();

        $debugger = $module->getDebugger();
        $this->assertInstanceOf(Debugger::class, $debugger);
    }

    public function testModuleRegistersStorageInContainer(): void
    {
        $this->createWebApplication();

        $this->assertTrue(\Yii::$container->has(StorageInterface::class));
        $this->assertTrue(\Yii::$container->has(DebuggerIdGenerator::class));
        $this->assertTrue(\Yii::$container->has(Debugger::class));
    }

    public function testModuleRegistersApiApplicationInContainer(): void
    {
        $this->createWebApplication();

        $this->assertTrue(\Yii::$container->has(ApiApplication::class));
    }

    public function testGetCollectorByClass(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();

        $dbCollector = $module->getCollector(DatabaseCollector::class);
        $this->assertInstanceOf(DatabaseCollector::class, $dbCollector);

        $timelineCollector = $module->getCollector(TimelineCollector::class);
        $this->assertInstanceOf(TimelineCollector::class, $timelineCollector);

        $this->assertNull($module->getCollector(\stdClass::class));
    }

    public function testSelectiveCollectorDisabling(): void
    {
        $this->createWebApplication([
            'collectors' => [
                'request' => true,
                'exception' => true,
                'log' => true,
                'event' => true,
                'service' => true,
                'http_client' => true,
                'timeline' => true,
                'var_dumper' => true,
                'filesystem_stream' => true,
                'http_stream' => true,
                'command' => true,
                'db' => false,
                'mailer' => true,
                'assets' => true,
            ],
        ]);
        $module = $this->getModule();

        $collectorClasses = array_map(static fn($c) => $c::class, $module->getCollectorInstances());

        $this->assertNotContains(DatabaseCollector::class, $collectorClasses);
        $this->assertContains(LogCollector::class, $collectorClasses);
        $this->assertContains(RequestCollector::class, $collectorClasses);
    }

    public function testTimelineCollectorIsSingleton(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();

        $timeline1 = $module->getTimelineCollector();
        $timeline2 = $module->getTimelineCollector();

        $this->assertSame($timeline1, $timeline2);
    }

    public function testDebuggerLifecycleStartupShutdown(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/test');

        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        /** @var DatabaseCollector $dbCollector */
        $dbCollector = $module->getCollector(DatabaseCollector::class);
        $startTime = microtime(true);
        $dbCollector->logQuery('SELECT 1', 'SELECT 1', [], '', $startTime, microtime(true), 1);

        $debugger->shutdown();

        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries, 'Storage should contain at least one summary entry after flush');
    }

    public function testModuleDisabledSkipsBootstrap(): void
    {
        $this->createWebApplication(['enabled' => false]);

        // Module exists but should not have initialized the debugger
        $module = \Yii::$app->getModule('debug-panel');
        $this->assertInstanceOf(Module::class, $module);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Debugger is not initialized');
        $module->getDebugger();
    }
}

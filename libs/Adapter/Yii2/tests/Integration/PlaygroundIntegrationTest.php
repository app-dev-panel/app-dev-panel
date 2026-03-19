<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Adapter\Yii2\Collector\AssetBundleCollector;
use AppDevPanel\Adapter\Yii2\Collector\DbCollector;
use AppDevPanel\Adapter\Yii2\Collector\MailerCollector;
use AppDevPanel\Adapter\Yii2\Module;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\FileStorage;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Integration test that simulates the Yii 2 playground app lifecycle.
 *
 * Bootstraps a real Module, feeds it request data through the Debugger,
 * and verifies that debug data is correctly flushed to FileStorage.
 * Does not require a running HTTP server.
 */
#[CoversNothing]
final class PlaygroundIntegrationTest extends TestCase
{
    private string $storagePath;

    protected function setUp(): void
    {
        $this->storagePath = sys_get_temp_dir() . '/adp_playground_test_' . bin2hex(random_bytes(8));
        mkdir($this->storagePath, 0o777, true);
        mkdir($this->storagePath . '/runtime', 0o777, true);
        mkdir($this->storagePath . '/debug', 0o777, true);

        \Yii::$container = new \yii\di\Container();
        \Yii::setAlias('@app', $this->storagePath);
        \Yii::setAlias('@runtime', $this->storagePath . '/runtime');
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        $this->removeDirectory($this->storagePath);
    }

    public function testModuleBootstrapRegistersDebugger(): void
    {
        $module = $this->createBootstrappedModule();

        $this->assertInstanceOf(Debugger::class, $module->getDebugger());
        $this->assertNotEmpty($module->getCollectorInstances());
    }

    public function testWebRequestLifecycleProducesStorageEntry(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        // Simulate a web request lifecycle
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/users');

        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        // Simulate collectors receiving data (as they would from event listeners)
        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        /** @var WebAppInfoCollector|null $webAppInfoCollector */
        $webAppInfoCollector = $module->getCollector(WebAppInfoCollector::class);
        $webAppInfoCollector?->markApplicationStarted();
        $webAppInfoCollector?->markRequestStarted();

        // Simulate DB queries with timing
        /** @var DbCollector|null $dbCollector */
        $dbCollector = $module->getCollector(DbCollector::class);
        $dbCollector?->beginQuery();
        $dbCollector?->logQuery('SELECT * FROM users LIMIT 10', [], 3);
        $dbCollector?->beginQuery();
        $dbCollector?->logQuery('SELECT COUNT(*) FROM users', [], 1);

        // Simulate response
        $psrResponse = $psr17->createResponse(200);
        $requestCollector?->collectResponse($psrResponse);

        $webAppInfoCollector?->markRequestFinished();
        $webAppInfoCollector?->markApplicationFinished();

        // Shutdown — this flushes to storage
        $debugger->shutdown();

        // Verify data was persisted to FileStorage
        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries, 'Summaries should not be empty after flush');

        // Find our entry
        $this->assertArrayHasKey($debugId, $summaries);
        $summary = $summaries[$debugId];
        $this->assertArrayHasKey('collectors', $summary);
    }

    public function testDataEntryContainsCollectorPayloads(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('POST', 'http://localhost/api/submit');
        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $psrResponse = $psr17->createResponse(201);
        $requestCollector?->collectResponse($psrResponse);

        /** @var DbCollector|null $dbCollector */
        $dbCollector = $module->getCollector(DbCollector::class);
        $dbCollector?->beginQuery();
        $dbCollector?->logQuery('INSERT INTO submissions (data) VALUES (?)', ['test'], 1);

        $debugger->shutdown();

        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);

        $data = $storage->read(StorageInterface::TYPE_DATA);
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey($debugId, $data);

        $entry = $data[$debugId];

        // Verify RequestCollector data
        $this->assertArrayHasKey(RequestCollector::class, $entry);
        $requestData = $entry[RequestCollector::class];
        $this->assertSame('POST', $requestData['requestMethod']);
        $this->assertSame(201, $requestData['responseStatusCode']);

        // Verify DbCollector data
        $this->assertArrayHasKey(DbCollector::class, $entry);
        $dbData = $entry[DbCollector::class];
        $this->assertCount(1, $dbData['queries']);
        $this->assertStringContainsString('INSERT INTO submissions', $dbData['queries'][0]['sql']);
    }

    public function testExceptionCollectorCapturesErrors(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/error');
        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));

        /** @var ExceptionCollector|null $exceptionCollector */
        $exceptionCollector = $module->getCollector(ExceptionCollector::class);
        $exceptionCollector?->collect(new \RuntimeException('Demo exception', 500));

        $debugger->shutdown();

        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $data = $storage->read(StorageInterface::TYPE_DATA);

        $debugId = $debugger->getId();
        $this->assertArrayHasKey($debugId, $data);

        $entry = $data[$debugId];
        $this->assertArrayHasKey(ExceptionCollector::class, $entry);

        $exceptionData = $entry[ExceptionCollector::class];
        $this->assertNotEmpty($exceptionData);
        $this->assertSame(\RuntimeException::class, $exceptionData[0]['class']);
        $this->assertSame('Demo exception', $exceptionData[0]['message']);
    }

    public function testConsoleCommandLifecycle(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();

        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forCommand('test-logging'));
        $debugId = $debugger->getId();

        $debugger->shutdown();

        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);

        $this->assertArrayHasKey($debugId, $summaries);
    }

    public function testMultipleRequestsProduceMultipleEntries(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

        $ids = [];

        // First request
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/');
        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));
        $ids[] = $debugger->getId();
        $debugger->shutdown();

        // Second request
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/users');
        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));
        $ids[] = $debugger->getId();
        $debugger->shutdown();

        // Verify both entries exist
        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);

        foreach ($ids as $id) {
            $this->assertArrayHasKey($id, $summaries, "Entry $id should exist in storage");
        }

        // IDs should be different
        $this->assertNotSame($ids[0], $ids[1]);
    }

    public function testIgnoredRequestsAreNotStored(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

        // This request matches the ignoredRequests pattern
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/debug/api/entries');
        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));
        $debugger->shutdown();

        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);

        // The debug API request should have been ignored
        $this->assertEmpty($summaries, 'Ignored requests should not produce storage entries');
    }

    public function testStorageFormatIsCorrect(): void
    {
        $module = $this->createBootstrappedModule();
        $debugger = $module->getDebugger();
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/test');
        $debugger->startup(\AppDevPanel\Kernel\StartupContext::forRequest($psrRequest));

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $debugger->shutdown();
        $debugId = $debugger->getId();

        /** @var FileStorage $storage */
        $storage = \Yii::$container->get(StorageInterface::class);

        // Summary format check
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertArrayHasKey($debugId, $summaries);

        // Data format check
        $data = $storage->read(StorageInterface::TYPE_DATA);
        $this->assertArrayHasKey($debugId, $data);
        $entry = $data[$debugId];

        // Verify collector keys are FQCN strings
        foreach (array_keys($entry) as $key) {
            $this->assertIsString($key);
            $this->assertStringContainsString('\\', $key, "Collector key should be a FQCN: $key");
        }
    }

    private function createBootstrappedModule(): Module
    {
        $module = new Module('debug-panel', null, [
            'storagePath' => $this->storagePath . '/debug',
            'historySize' => 50,
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
                'db' => true,
                'mailer' => true,
                'assets' => true,
            ],
        ]);

        // Manually call bootstrap internals
        $reflection = new \ReflectionClass($module);

        $registerServices = $reflection->getMethod('registerServices');
        $registerServices->setAccessible(true);
        $registerServices->invoke($module, \Yii::$app);

        $registerCollectors = $reflection->getMethod('registerCollectors');
        $registerCollectors->setAccessible(true);
        $registerCollectors->invoke($module);

        $buildDebugger = $reflection->getMethod('buildDebugger');
        $buildDebugger->setAccessible(true);
        $buildDebugger->invoke($module);

        return $module;
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

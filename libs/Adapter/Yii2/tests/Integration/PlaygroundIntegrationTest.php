<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Integration;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\QueryRecord;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Collector\Web\WebAppInfoCollector;
use AppDevPanel\Kernel\StartupContext;
use AppDevPanel\Kernel\Storage\StorageInterface;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Integration test that simulates the Yii 2 playground app lifecycle.
 *
 * Bootstraps a real Yii 2 Application with the ADP Module, feeds it
 * request data through the Debugger, and verifies that debug data
 * is correctly flushed to storage.
 */
#[CoversNothing]
final class PlaygroundIntegrationTest extends Yii2IntegrationTestCase
{
    public function testModuleBootstrapRegistersDebugger(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();

        $this->assertNotNull($module->getDebugger());
        $this->assertNotEmpty($module->getCollectorInstances());
    }

    public function testWebRequestLifecycleProducesStorageEntry(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/users');

        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();
        $this->assertNotEmpty($debugId);

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        /** @var WebAppInfoCollector|null $webAppInfoCollector */
        $webAppInfoCollector = $module->getCollector(WebAppInfoCollector::class);
        $webAppInfoCollector?->markApplicationStarted();
        $webAppInfoCollector?->markRequestStarted();

        /** @var DatabaseCollector|null $dbCollector */
        $dbCollector = $module->getCollector(DatabaseCollector::class);
        $startTime = microtime(true);
        $dbCollector?->logQuery(
            new QueryRecord(
                'SELECT * FROM users LIMIT 10',
                'SELECT * FROM users LIMIT 10',
                [],
                '',
                $startTime,
                microtime(true),
                3,
            ),
        );
        $startTime = microtime(true);
        $dbCollector?->logQuery(
            new QueryRecord(
                'SELECT COUNT(*) FROM users',
                'SELECT COUNT(*) FROM users',
                [],
                '',
                $startTime,
                microtime(true),
                1,
            ),
        );

        $psrResponse = $psr17->createResponse(200);
        $requestCollector?->collectResponse($psrResponse);

        $webAppInfoCollector?->markRequestFinished();
        $webAppInfoCollector?->markApplicationFinished();

        $debugger->shutdown();

        /** @var StorageInterface $storage */
        $storage = \Yii::$container->get(StorageInterface::class);

        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);
        $this->assertNotEmpty($summaries, 'Summaries should not be empty after flush');

        $this->assertArrayHasKey($debugId, $summaries);
        $summary = $summaries[$debugId];
        $this->assertArrayHasKey('collectors', $summary);
    }

    public function testDataEntryContainsCollectorPayloads(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('POST', 'http://localhost/api/submit');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugId = $debugger->getId();

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $psrResponse = $psr17->createResponse(201);
        $requestCollector?->collectResponse($psrResponse);

        /** @var DatabaseCollector|null $dbCollector */
        $dbCollector = $module->getCollector(DatabaseCollector::class);
        $startTime = microtime(true);
        $dbCollector?->logQuery(
            new QueryRecord(
                'INSERT INTO submissions (data) VALUES (?)',
                'INSERT INTO submissions (data) VALUES (?)',
                ['test'],
                '',
                $startTime,
                microtime(true),
                1,
            ),
        );

        $debugger->shutdown();

        /** @var StorageInterface $storage */
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

        // Verify DatabaseCollector data
        $this->assertArrayHasKey(DatabaseCollector::class, $entry);
        $dbData = $entry[DatabaseCollector::class];
        $this->assertCount(1, $dbData['queries']);
        $this->assertStringContainsString('INSERT INTO submissions', $dbData['queries'][0]['sql']);
    }

    public function testExceptionCollectorCapturesErrors(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();

        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/error');
        $debugger->startup(StartupContext::forRequest($psrRequest));

        /** @var ExceptionCollector|null $exceptionCollector */
        $exceptionCollector = $module->getCollector(ExceptionCollector::class);
        $exceptionCollector?->collect(new \RuntimeException('Demo exception', 500));

        $debugger->shutdown();

        /** @var StorageInterface $storage */
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
        $this->createConsoleApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();

        $debugger->startup(StartupContext::forCommand('test-logging'));
        $debugId = $debugger->getId();

        $debugger->shutdown();

        /** @var StorageInterface $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);

        $this->assertArrayHasKey($debugId, $summaries);
    }

    public function testMultipleRequestsProduceMultipleEntries(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

        $ids = [];

        // First request
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $ids[] = $debugger->getId();
        $debugger->shutdown();

        // Second request
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/api/users');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $ids[] = $debugger->getId();
        $debugger->shutdown();

        /** @var StorageInterface $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);

        foreach ($ids as $id) {
            $this->assertArrayHasKey($id, $summaries, "Entry {$id} should exist in storage");
        }

        $this->assertNotSame($ids[0], $ids[1]);
    }

    public function testIgnoredRequestsAreNotStored(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

        // This request matches the ignoredRequests pattern
        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/debug/api/entries');
        $debugger->startup(StartupContext::forRequest($psrRequest));
        $debugger->shutdown();

        /** @var StorageInterface $storage */
        $storage = \Yii::$container->get(StorageInterface::class);
        $summaries = $storage->read(StorageInterface::TYPE_SUMMARY);

        $this->assertEmpty($summaries, 'Ignored requests should not produce storage entries');
    }

    public function testStorageFormatIsCorrect(): void
    {
        $this->createWebApplication();
        $module = $this->getModule();
        $debugger = $module->getDebugger();
        $psr17 = new \Nyholm\Psr7\Factory\Psr17Factory();

        $psrRequest = $psr17->createServerRequest('GET', 'http://localhost/test');
        $debugger->startup(StartupContext::forRequest($psrRequest));

        /** @var RequestCollector|null $requestCollector */
        $requestCollector = $module->getCollector(RequestCollector::class);
        $requestCollector?->collectRequest($psrRequest);

        $debugger->shutdown();
        $debugId = $debugger->getId();

        /** @var StorageInterface $storage */
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
            $this->assertStringContainsString('\\', $key, "Collector key should be a FQCN: {$key}");
        }
    }
}

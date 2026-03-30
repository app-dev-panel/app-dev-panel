<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\ElasticsearchCollector;
use AppDevPanel\Kernel\Collector\ElasticsearchRequestRecord;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class ElasticsearchCollectorTest extends AbstractCollectorTestCase
{
    /**
     * @param CollectorInterface|ElasticsearchCollector $collector
     */
    protected function collectTestData(CollectorInterface $collector): void
    {
        $collector->collectRequestStart('r1', 'GET', '/users/_search', '{"query":{"match_all":{}}}', 'Repo.php:10');
        $collector->collectRequestEnd('r1', 200, '{"hits":{"total":{"value":15},"hits":[]}}', 1024);

        $collector->collectRequestStart('r2', 'POST', '/orders/_doc', '{"amount":100}', 'Service.php:25');
        $collector->collectRequestEnd('r2', 201, '{"_id":"abc","result":"created"}', 128);

        $collector->collectRequestStart('r3', 'DELETE', '/temp/_doc/1', '', 'Cleanup.php:5');
        $collector->collectRequestError('r3', new \RuntimeException('Connection refused'));
    }

    protected function getCollector(): CollectorInterface
    {
        return new ElasticsearchCollector(new TimelineCollector());
    }

    protected function checkCollectedData(array $data): void
    {
        $this->assertArrayHasKey('requests', $data);
        $this->assertArrayHasKey('duplicates', $data);
        $this->assertCount(3, $data['requests']);

        $r1 = $data['requests'][0];
        $this->assertSame('GET', $r1['method']);
        $this->assertSame('/users/_search', $r1['endpoint']);
        $this->assertSame('users', $r1['index']);
        $this->assertSame('success', $r1['status']);
        $this->assertSame(200, $r1['statusCode']);
        $this->assertSame(1024, $r1['responseSize']);
        $this->assertSame(15, $r1['hitsCount']);
        $this->assertGreaterThan(0, $r1['duration']);

        $r2 = $data['requests'][1];
        $this->assertSame('POST', $r2['method']);
        $this->assertSame('orders', $r2['index']);
        $this->assertSame('success', $r2['status']);
        $this->assertSame(201, $r2['statusCode']);
        $this->assertNull($r2['hitsCount']);

        $r3 = $data['requests'][2];
        $this->assertSame('DELETE', $r3['method']);
        $this->assertSame('temp', $r3['index']);
        $this->assertSame('error', $r3['status']);
        $this->assertNotNull($r3['exception']);
    }

    protected function checkSummaryData(array $data): void
    {
        $this->assertArrayHasKey('elasticsearch', $data);
        $es = $data['elasticsearch'];
        $this->assertSame(3, $es['total']);
        $this->assertSame(1, $es['errors']);
        $this->assertIsFloat($es['totalTime']);
        $this->assertGreaterThanOrEqual(0, $es['totalTime']);
        $this->assertArrayHasKey('duplicateGroups', $es);
        $this->assertArrayHasKey('totalDuplicatedCount', $es);
    }

    public function testLogRequest(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $collector->startup();

        $record = new ElasticsearchRequestRecord(
            method: 'GET',
            endpoint: '/products/_search',
            body: '{"query":{"match_all":{}}}',
            line: 'Search.php:42',
            startTime: 100.0,
            endTime: 100.05,
            statusCode: 200,
            responseBody: '{"hits":{"total":{"value":5},"hits":[]}}',
            responseSize: 512,
        );
        $collector->logRequest($record);

        $data = $collector->getCollected();
        $this->assertCount(1, $data['requests']);

        $r = $data['requests'][0];
        $this->assertSame('GET', $r['method']);
        $this->assertSame('/products/_search', $r['endpoint']);
        $this->assertSame('products', $r['index']);
        $this->assertSame('success', $r['status']);
        $this->assertSame(200, $r['statusCode']);
        $this->assertSame(0.05, round($r['duration'], 2));
        $this->assertSame(5, $r['hitsCount']);
    }

    public function testLogRequestWithError(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $collector->startup();

        $record = new ElasticsearchRequestRecord(
            method: 'GET',
            endpoint: '/missing/_search',
            body: '{}',
            line: 'Test.php:1',
            startTime: 100.0,
            endTime: 100.01,
            statusCode: 404,
            responseBody: '{"error":"index_not_found_exception"}',
            responseSize: 64,
        );
        $collector->logRequest($record);

        $data = $collector->getCollected();
        $this->assertSame('error', $data['requests'][0]['status']);
    }

    public function testExtractIndexFromClusterEndpoint(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectRequestStart('r1', 'GET', '/_cat/indices', '', 'Test.php:1');
        $collector->collectRequestEnd('r1', 200, '', 0);

        $data = $collector->getCollected();
        $this->assertSame('', $data['requests'][0]['index']);
    }

    public function testDuplicateDetection(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $collector->startup();

        // Make 3+ identical requests to trigger duplicate detection (threshold is 2)
        for ($i = 0; $i < 3; $i++) {
            $collector->collectRequestStart("r{$i}", 'GET', '/users/_search', '{}', 'Test.php:1');
            $collector->collectRequestEnd("r{$i}", 200, '', 0);
        }

        $data = $collector->getCollected();
        $this->assertCount(1, $data['duplicates']['groups']);
        $this->assertSame(3, $data['duplicates']['totalDuplicatedCount']);
    }

    public function testNameIsElasticsearch(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $this->assertSame('Elasticsearch', $collector->getName());
    }

    public function testCollectRequestEndForUnknownIdIsIgnored(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectRequestEnd('unknown', 200, '', 0);

        $data = $collector->getCollected();
        $this->assertSame([], $data['requests']);
    }

    public function testCollectRequestErrorForUnknownIdIsIgnored(): void
    {
        $collector = new ElasticsearchCollector(new TimelineCollector());
        $collector->startup();

        $collector->collectRequestError('unknown', new \RuntimeException('test'));

        $data = $collector->getCollected();
        $this->assertSame([], $data['requests']);
    }
}

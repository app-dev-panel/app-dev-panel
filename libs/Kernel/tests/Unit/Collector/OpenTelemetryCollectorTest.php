<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\SpanRecord;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use AppDevPanel\Kernel\Tests\Shared\AbstractCollectorTestCase;

final class OpenTelemetryCollectorTest extends AbstractCollectorTestCase
{
    protected function getCollector(): CollectorInterface
    {
        return new OpenTelemetryCollector(new TimelineCollector());
    }

    protected function collectTestData(CollectorInterface $collector): void
    {
        assert($collector instanceof OpenTelemetryCollector);

        $collector->collect(new SpanRecord(
            traceId: 'aaaa1111bbbb2222cccc3333dddd4444',
            spanId: '1111222233334444',
            parentSpanId: null,
            operationName: 'GET /api/users',
            serviceName: 'user-service',
            startTime: 1700000000.0,
            endTime: 1700000000.150,
            duration: 150.0,
            status: 'OK',
            kind: 'SERVER',
            attributes: ['http.method' => 'GET', 'http.status_code' => 200],
        ));

        $collector->collect(new SpanRecord(
            traceId: 'aaaa1111bbbb2222cccc3333dddd4444',
            spanId: '5555666677778888',
            parentSpanId: '1111222233334444',
            operationName: 'SELECT users',
            serviceName: 'user-service',
            startTime: 1700000000.010,
            endTime: 1700000000.050,
            duration: 40.0,
            status: 'OK',
            kind: 'CLIENT',
            attributes: ['db.system' => 'postgresql'],
        ));

        $collector->collect(new SpanRecord(
            traceId: 'eeee5555ffff6666aaaa7777bbbb8888',
            spanId: '9999aaaabbbbcccc',
            parentSpanId: null,
            operationName: 'POST /api/orders',
            serviceName: 'order-service',
            startTime: 1700000001.0,
            endTime: 1700000001.500,
            duration: 500.0,
            status: 'ERROR',
            statusMessage: 'Internal server error',
            kind: 'SERVER',
        ));
    }

    protected function checkCollectedData(array $data): void
    {
        parent::checkCollectedData($data);

        $this->assertArrayHasKey('spans', $data);
        $this->assertArrayHasKey('traceCount', $data);
        $this->assertArrayHasKey('spanCount', $data);
        $this->assertArrayHasKey('errorCount', $data);

        $this->assertCount(3, $data['spans']);
        $this->assertSame(2, $data['traceCount']);
        $this->assertSame(3, $data['spanCount']);
        $this->assertSame(1, $data['errorCount']);

        $firstSpan = $data['spans'][0];
        $this->assertSame('aaaa1111bbbb2222cccc3333dddd4444', $firstSpan['traceId']);
        $this->assertSame('1111222233334444', $firstSpan['spanId']);
        $this->assertNull($firstSpan['parentSpanId']);
        $this->assertSame('GET /api/users', $firstSpan['operationName']);
        $this->assertSame('user-service', $firstSpan['serviceName']);
        $this->assertSame('OK', $firstSpan['status']);
        $this->assertSame('SERVER', $firstSpan['kind']);
        $this->assertSame(150.0, $firstSpan['duration']);
        $this->assertSame('GET', $firstSpan['attributes']['http.method']);

        $childSpan = $data['spans'][1];
        $this->assertSame('1111222233334444', $childSpan['parentSpanId']);

        $errorSpan = $data['spans'][2];
        $this->assertSame('ERROR', $errorSpan['status']);
        $this->assertSame('Internal server error', $errorSpan['statusMessage']);
    }

    protected function checkSummaryData(array $data): void
    {
        parent::checkSummaryData($data);

        $this->assertArrayHasKey('opentelemetry', $data);
        $this->assertSame(3, $data['opentelemetry']['spans']);
        $this->assertSame(2, $data['opentelemetry']['traces']);
        $this->assertSame(1, $data['opentelemetry']['errors']);
    }

    public function testCollectBatch(): void
    {
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $spans = [
            new SpanRecord(
                traceId: 'trace1',
                spanId: 'span1',
                parentSpanId: null,
                operationName: 'op1',
                serviceName: 'svc',
                startTime: 1.0,
                endTime: 2.0,
                duration: 1000.0,
            ),
            new SpanRecord(
                traceId: 'trace1',
                spanId: 'span2',
                parentSpanId: 'span1',
                operationName: 'op2',
                serviceName: 'svc',
                startTime: 1.1,
                endTime: 1.5,
                duration: 400.0,
            ),
        ];

        $collector->collectBatch($spans);

        $data = $collector->getCollected();
        $this->assertSame(2, $data['spanCount']);
        $this->assertSame(1, $data['traceCount']);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\SpanRecord;
use PHPUnit\Framework\TestCase;

final class SpanRecordTest extends TestCase
{
    public function testToArray(): void
    {
        $events = [
            ['name' => 'exception', 'timestamp' => 1700000000.5, 'attributes' => ['message' => 'test error']],
        ];
        $links = [
            ['traceId' => 'linked-trace', 'spanId' => 'linked-span', 'attributes' => []],
        ];

        $span = new SpanRecord(
            traceId: 'aaaa1111bbbb2222cccc3333dddd4444',
            spanId: '1111222233334444',
            parentSpanId: '0000111122223333',
            operationName: 'GET /api/users',
            serviceName: 'user-service',
            startTime: 1700000000.0,
            endTime: 1700000000.150,
            duration: 150.0,
            status: 'ERROR',
            statusMessage: 'Not found',
            kind: 'SERVER',
            attributes: ['http.method' => 'GET', 'http.status_code' => 404],
            events: $events,
            links: $links,
            resourceAttributes: ['service.version' => '1.0.0'],
        );

        $array = $span->toArray();

        $this->assertSame('aaaa1111bbbb2222cccc3333dddd4444', $array['traceId']);
        $this->assertSame('1111222233334444', $array['spanId']);
        $this->assertSame('0000111122223333', $array['parentSpanId']);
        $this->assertSame('GET /api/users', $array['operationName']);
        $this->assertSame('user-service', $array['serviceName']);
        $this->assertSame(1700000000.0, $array['startTime']);
        $this->assertSame(1700000000.150, $array['endTime']);
        $this->assertSame(150.0, $array['duration']);
        $this->assertSame('ERROR', $array['status']);
        $this->assertSame('Not found', $array['statusMessage']);
        $this->assertSame('SERVER', $array['kind']);
        $this->assertSame(['http.method' => 'GET', 'http.status_code' => 404], $array['attributes']);
        $this->assertSame($events, $array['events']);
        $this->assertSame($links, $array['links']);
        $this->assertSame(['service.version' => '1.0.0'], $array['resourceAttributes']);
    }

    public function testDefaults(): void
    {
        $span = new SpanRecord(
            traceId: 'trace1',
            spanId: 'span1',
            parentSpanId: null,
            operationName: 'op',
            serviceName: 'svc',
            startTime: 1.0,
            endTime: 2.0,
            duration: 1000.0,
        );

        $this->assertSame('UNSET', $span->status);
        $this->assertSame('', $span->statusMessage);
        $this->assertSame('INTERNAL', $span->kind);
        $this->assertSame([], $span->attributes);
        $this->assertSame([], $span->events);
        $this->assertSame([], $span->links);
        $this->assertSame([], $span->resourceAttributes);
    }
}

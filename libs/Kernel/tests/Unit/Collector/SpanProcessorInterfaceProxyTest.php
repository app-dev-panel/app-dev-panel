<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Collector;

use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\SpanProcessorInterfaceProxy;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanDataInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;
use OpenTelemetry\SDK\Trace\StatusData;
use PHPUnit\Framework\TestCase;

final class SpanProcessorInterfaceProxyTest extends TestCase
{
    public function testOnEndCollectsSpanAndDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('aaaa1111bbbb2222cccc3333dddd4444');
        $spanData->method('getSpanId')->willReturn('1111222233334444');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('GET /api/users');
        $spanData->method('getStartEpochNanos')->willReturn(1700000000000000000);
        $spanData->method('getEndEpochNanos')->willReturn(1700000000150000000);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_SERVER);
        $spanData->method('getStatus')->willReturn(StatusData::ok());
        $spanData->method('getAttributes')->willReturn(Attributes::create(['http.method' => 'GET']));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([]);
        $spanData
            ->method('getResource')
            ->willReturn(ResourceInfo::create(Attributes::create(['service.name' => 'test-service'])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $decorated->expects($this->once())->method('onEnd')->with($readableSpan);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertSame(1, $data['spanCount']);
        $this->assertSame(1, $data['traceCount']);

        $span = $data['spans'][0];
        $this->assertSame('aaaa1111bbbb2222cccc3333dddd4444', $span['traceId']);
        $this->assertSame('1111222233334444', $span['spanId']);
        $this->assertNull($span['parentSpanId']);
        $this->assertSame('GET /api/users', $span['operationName']);
        $this->assertSame('test-service', $span['serviceName']);
        $this->assertSame('OK', $span['status']);
        $this->assertSame('SERVER', $span['kind']);
        $this->assertSame('GET', $span['attributes']['http.method']);
    }

    public function testOnStartDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $span = $this->createMock(ReadWriteSpanInterface::class);
        $context = $this->createMock(ContextInterface::class);

        $decorated->expects($this->once())->method('onStart')->with($span, $context);

        $proxy->onStart($span, $context);
    }

    public function testForceFlushDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $decorated->expects($this->once())->method('forceFlush')->willReturn(true);

        $this->assertTrue($proxy->forceFlush());
    }

    public function testShutdownDelegatesToDecorated(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $decorated->expects($this->once())->method('shutdown')->willReturn(true);

        $this->assertTrue($proxy->shutdown());
    }

    public function testParentSpanIdNullWhenEmpty(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('0000000000000000');
        $spanData->method('getName')->willReturn('root');
        $spanData->method('getStartEpochNanos')->willReturn(0);
        $spanData->method('getEndEpochNanos')->willReturn(0);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_INTERNAL);
        $spanData->method('getStatus')->willReturn(StatusData::unset());
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([]);
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create([])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertNull($data['spans'][0]['parentSpanId']);
    }

    public function testErrorSpanStatus(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('failing-op');
        $spanData->method('getStartEpochNanos')->willReturn(0);
        $spanData->method('getEndEpochNanos')->willReturn(1000000000);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_CLIENT);
        $spanData->method('getStatus')->willReturn(StatusData::create(StatusCode::STATUS_ERROR, 'Connection refused'));
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([]);
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create([])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertSame('ERROR', $data['spans'][0]['status']);
        $this->assertSame('Connection refused', $data['spans'][0]['statusMessage']);
        $this->assertSame(1, $data['errorCount']);
    }
}

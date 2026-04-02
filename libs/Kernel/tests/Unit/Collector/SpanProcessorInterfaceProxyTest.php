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

    public function testForceFlushWithCancellation(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $cancellation = $this->createMock(CancellationInterface::class);
        $decorated->expects($this->once())->method('forceFlush')->with($cancellation)->willReturn(false);

        $this->assertFalse($proxy->forceFlush($cancellation));
    }

    public function testShutdownWithCancellation(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $cancellation = $this->createMock(CancellationInterface::class);
        $decorated->expects($this->once())->method('shutdown')->with($cancellation)->willReturn(false);

        $this->assertFalse($proxy->shutdown($cancellation));
    }

    public function testSpanWithParentSpanId(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span2');
        $spanData->method('getParentSpanId')->willReturn('span1');
        $spanData->method('getName')->willReturn('child-op');
        $spanData->method('getStartEpochNanos')->willReturn(1000000000);
        $spanData->method('getEndEpochNanos')->willReturn(2000000000);
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
        $this->assertSame('span1', $data['spans'][0]['parentSpanId']);
        $this->assertSame('UNSET', $data['spans'][0]['status']);
        $this->assertSame('INTERNAL', $data['spans'][0]['kind']);
    }

    public function testSpanWithEvents(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $event = $this->createMock(\OpenTelemetry\SDK\Trace\EventInterface::class);
        $event->method('getName')->willReturn('exception');
        $event->method('getEpochNanos')->willReturn(1500000000000000000);
        $event->method('getAttributes')->willReturn(Attributes::create(['exception.message' => 'Something failed']));

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('op-with-events');
        $spanData->method('getStartEpochNanos')->willReturn(1000000000000000000);
        $spanData->method('getEndEpochNanos')->willReturn(2000000000000000000);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_SERVER);
        $spanData->method('getStatus')->willReturn(StatusData::ok());
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([$event]);
        $spanData->method('getLinks')->willReturn([]);
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create([])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertCount(1, $data['spans'][0]['events']);
        $this->assertSame('exception', $data['spans'][0]['events'][0]['name']);
        $this->assertSame('Something failed', $data['spans'][0]['events'][0]['attributes']['exception.message']);
    }

    public function testSpanWithLinks(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanContext = $this->createMock(\OpenTelemetry\API\Trace\SpanContextInterface::class);
        $spanContext->method('getTraceId')->willReturn('linked-trace');
        $spanContext->method('getSpanId')->willReturn('linked-span');

        $link = $this->createMock(\OpenTelemetry\SDK\Trace\LinkInterface::class);
        $link->method('getSpanContext')->willReturn($spanContext);
        $link->method('getAttributes')->willReturn(Attributes::create(['link.attr' => 'val']));

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('op-with-links');
        $spanData->method('getStartEpochNanos')->willReturn(0);
        $spanData->method('getEndEpochNanos')->willReturn(1000000000);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_PRODUCER);
        $spanData->method('getStatus')->willReturn(StatusData::ok());
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([$link]);
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create([])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertCount(1, $data['spans'][0]['links']);
        $this->assertSame('linked-trace', $data['spans'][0]['links'][0]['traceId']);
        $this->assertSame('linked-span', $data['spans'][0]['links'][0]['spanId']);
        $this->assertSame('val', $data['spans'][0]['links'][0]['attributes']['link.attr']);
        $this->assertSame('PRODUCER', $data['spans'][0]['kind']);
    }

    public function testSpanKindConsumer(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('consume');
        $spanData->method('getStartEpochNanos')->willReturn(0);
        $spanData->method('getEndEpochNanos')->willReturn(0);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_CONSUMER);
        $spanData->method('getStatus')->willReturn(StatusData::unset());
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([]);
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create([])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertSame('CONSUMER', $data['spans'][0]['kind']);
    }

    public function testServiceNameFallsBackToUnknown(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('op');
        $spanData->method('getStartEpochNanos')->willReturn(0);
        $spanData->method('getEndEpochNanos')->willReturn(0);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_INTERNAL);
        $spanData->method('getStatus')->willReturn(StatusData::unset());
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([]);
        // No service.name attribute
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create(['other.attr' => 'val'])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $this->assertSame('unknown', $data['spans'][0]['serviceName']);
        $this->assertSame('val', $data['spans'][0]['resourceAttributes']['other.attr']);
    }

    public function testDurationCalculation(): void
    {
        $decorated = $this->createMock(SpanProcessorInterface::class);
        $collector = new OpenTelemetryCollector(new TimelineCollector());
        $collector->startup();

        $proxy = new SpanProcessorInterfaceProxy($decorated, $collector);

        // 150ms span
        $spanData = $this->createMock(SpanDataInterface::class);
        $spanData->method('getTraceId')->willReturn('trace1');
        $spanData->method('getSpanId')->willReturn('span1');
        $spanData->method('getParentSpanId')->willReturn('');
        $spanData->method('getName')->willReturn('timed-op');
        $spanData->method('getStartEpochNanos')->willReturn(1700000000000000000);
        $spanData->method('getEndEpochNanos')->willReturn(1700000000150000000);
        $spanData->method('getKind')->willReturn(SpanKind::KIND_SERVER);
        $spanData->method('getStatus')->willReturn(StatusData::ok());
        $spanData->method('getAttributes')->willReturn(Attributes::create([]));
        $spanData->method('getEvents')->willReturn([]);
        $spanData->method('getLinks')->willReturn([]);
        $spanData->method('getResource')->willReturn(ResourceInfo::create(Attributes::create([])));

        $readableSpan = $this->createMock(ReadableSpanInterface::class);
        $readableSpan->method('toSpanData')->willReturn($spanData);

        $proxy->onEnd($readableSpan);

        $data = $collector->getCollected();
        $span = $data['spans'][0];
        $this->assertEqualsWithDelta(150.0, $span['duration'], 0.01);
        $this->assertEqualsWithDelta(1700000000.0, $span['startTime'], 0.01);
        $this->assertEqualsWithDelta(1700000000.15, $span['endTime'], 0.01);
    }
}

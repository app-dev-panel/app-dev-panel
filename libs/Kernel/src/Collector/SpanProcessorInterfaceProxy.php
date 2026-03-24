<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Context\ContextInterface;
use OpenTelemetry\SDK\Common\Future\CancellationInterface;
use OpenTelemetry\SDK\Trace\ReadableSpanInterface;
use OpenTelemetry\SDK\Trace\ReadWriteSpanInterface;
use OpenTelemetry\SDK\Trace\SpanProcessorInterface;

/**
 * Decorates an OpenTelemetry SpanProcessorInterface to capture span data
 * into the OpenTelemetryCollector without altering telemetry pipeline behaviour.
 *
 * Usage:
 *   $processor = new SpanProcessorInterfaceProxy($originalProcessor, $collector);
 *   $tracerProvider = TracerProviderBuilder::create()->addSpanProcessor($processor)->build();
 *
 * Requires: open-telemetry/sdk (optional dependency)
 */
final class SpanProcessorInterfaceProxy implements SpanProcessorInterface
{
    public function __construct(
        private readonly SpanProcessorInterface $decorated,
        private readonly OpenTelemetryCollector $collector,
    ) {}

    public function onStart(ReadWriteSpanInterface $span, ContextInterface $parentContext): void
    {
        $this->decorated->onStart($span, $parentContext);
    }

    public function onEnd(ReadableSpanInterface $span): void
    {
        $this->collectSpan($span);
        $this->decorated->onEnd($span);
    }

    public function forceFlush(?CancellationInterface $cancellation = null): bool
    {
        return $this->decorated->forceFlush($cancellation);
    }

    public function shutdown(?CancellationInterface $cancellation = null): bool
    {
        return $this->decorated->shutdown($cancellation);
    }

    private function collectSpan(ReadableSpanInterface $span): void
    {
        $spanData = $span->toSpanData();

        $startNanos = $spanData->getStartEpochNanos();
        $endNanos = $spanData->getEndEpochNanos();
        $startTime = $startNanos / 1e9;
        $endTime = $endNanos / 1e9;

        $serviceName = 'unknown';
        $resourceAttributes = [];
        $resource = $spanData->getResource();
        foreach ($resource->getAttributes() as $key => $value) {
            $resourceAttributes[$key] = $value;
            if ($key === 'service.name') {
                $serviceName = (string) $value;
            }
        }

        $attributes = [];
        foreach ($spanData->getAttributes() as $key => $value) {
            $attributes[$key] = $value;
        }

        $events = [];
        foreach ($spanData->getEvents() as $event) {
            $eventAttrs = [];
            foreach ($event->getAttributes() as $key => $value) {
                $eventAttrs[$key] = $value;
            }
            $events[] = [
                'name' => $event->getName(),
                'timestamp' => $event->getEpochNanos() / 1e9,
                'attributes' => $eventAttrs,
            ];
        }

        $links = [];
        foreach ($spanData->getLinks() as $link) {
            $linkAttrs = [];
            foreach ($link->getAttributes() as $key => $value) {
                $linkAttrs[$key] = $value;
            }
            $links[] = [
                'traceId' => $link->getSpanContext()->getTraceId(),
                'spanId' => $link->getSpanContext()->getSpanId(),
                'attributes' => $linkAttrs,
            ];
        }

        $parentSpanId = $spanData->getParentSpanId();
        if ($parentSpanId === '' || $parentSpanId === '0000000000000000') {
            $parentSpanId = null;
        }

        $status = $spanData->getStatus();

        $this->collector->collect(new SpanRecord(
            traceId: $spanData->getTraceId(),
            spanId: $spanData->getSpanId(),
            parentSpanId: $parentSpanId,
            operationName: $spanData->getName(),
            serviceName: $serviceName,
            startTime: $startTime,
            endTime: $endTime,
            duration: ($endTime - $startTime) * 1000,
            status: $this->mapStatus($status->getCode()),
            statusMessage: $status->getDescription(),
            kind: $this->mapKind($spanData->getKind()),
            attributes: $attributes,
            events: $events,
            links: $links,
            resourceAttributes: $resourceAttributes,
        ));
    }

    private function mapStatus(string $code): string
    {
        return match ($code) {
            StatusCode::STATUS_OK => 'OK',
            StatusCode::STATUS_ERROR => 'ERROR',
            default => 'UNSET',
        };
    }

    private function mapKind(int $kind): string
    {
        return match ($kind) {
            SpanKind::KIND_INTERNAL => 'INTERNAL',
            SpanKind::KIND_CLIENT => 'CLIENT',
            SpanKind::KIND_SERVER => 'SERVER',
            SpanKind::KIND_PRODUCER => 'PRODUCER',
            SpanKind::KIND_CONSUMER => 'CONSUMER',
            default => 'UNSPECIFIED',
        };
    }
}

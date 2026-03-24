<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final readonly class SpanRecord
{
    /**
     * @param array<string, mixed> $attributes
     * @param list<array{name: string, timestamp: float, attributes: array<string, mixed>}> $events
     * @param list<array{traceId: string, spanId: string, attributes: array<string, mixed>}> $links
     * @param array<string, mixed> $resourceAttributes
     */
    public function __construct(
        public string $traceId,
        public string $spanId,
        public ?string $parentSpanId,
        public string $operationName,
        public string $serviceName,
        public float $startTime,
        public float $endTime,
        public float $duration,
        public string $status = 'UNSET',
        public string $statusMessage = '',
        public string $kind = 'INTERNAL',
        public array $attributes = [],
        public array $events = [],
        public array $links = [],
        public array $resourceAttributes = [],
    ) {}

    public function toArray(): array
    {
        return [
            'traceId' => $this->traceId,
            'spanId' => $this->spanId,
            'parentSpanId' => $this->parentSpanId,
            'operationName' => $this->operationName,
            'serviceName' => $this->serviceName,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'duration' => $this->duration,
            'status' => $this->status,
            'statusMessage' => $this->statusMessage,
            'kind' => $this->kind,
            'attributes' => $this->attributes,
            'events' => $this->events,
            'links' => $this->links,
            'resourceAttributes' => $this->resourceAttributes,
        ];
    }
}

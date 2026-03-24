<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Parses OTLP JSON trace data (ExportTraceServiceRequest) into SpanRecord objects.
 *
 * @see https://opentelemetry.io/docs/specs/otlp/#otlphttp-request
 */
final class OtlpTraceParser
{
    /**
     * @return list<SpanRecord>
     */
    public function parse(array $data): array
    {
        $spans = [];

        foreach ($data['resourceSpans'] ?? [] as $resourceSpan) {
            $resourceAttributes = $this->parseAttributes($resourceSpan['resource']['attributes'] ?? []);
            $serviceName = $resourceAttributes['service.name'] ?? 'unknown';

            foreach ($resourceSpan['scopeSpans'] ?? [] as $scopeSpan) {
                foreach ($scopeSpan['spans'] ?? [] as $span) {
                    $spans[] = $this->parseSpan($span, (string) $serviceName, $resourceAttributes);
                }
            }
        }

        return $spans;
    }

    /**
     * @param array<string, mixed> $resourceAttributes
     */
    private function parseSpan(array $span, string $serviceName, array $resourceAttributes): SpanRecord
    {
        $startTimeUnixNano = $this->parseTimestamp($span['startTimeUnixNano'] ?? '0');
        $endTimeUnixNano = $this->parseTimestamp($span['endTimeUnixNano'] ?? '0');
        $startTime = $startTimeUnixNano / 1e9;
        $endTime = $endTimeUnixNano / 1e9;

        return new SpanRecord(
            traceId: $span['traceId'] ?? '',
            spanId: $span['spanId'] ?? '',
            parentSpanId: !empty($span['parentSpanId']) ? $span['parentSpanId'] : null,
            operationName: $span['name'] ?? '',
            serviceName: $serviceName,
            startTime: $startTime,
            endTime: $endTime,
            duration: ($endTime - $startTime) * 1000, // milliseconds
            status: $this->parseStatus($span['status'] ?? []),
            statusMessage: (string) ($span['status']['message'] ?? ''),
            kind: $this->parseKind($span['kind'] ?? 0),
            attributes: $this->parseAttributes($span['attributes'] ?? []),
            events: $this->parseEvents($span['events'] ?? []),
            links: $this->parseLinks($span['links'] ?? []),
            resourceAttributes: $resourceAttributes,
        );
    }

    /**
     * Parses OTLP key-value attributes into a flat associative array.
     *
     * @return array<string, mixed>
     */
    private function parseAttributes(array $attributes): array
    {
        $result = [];
        foreach ($attributes as $attr) {
            $key = $attr['key'] ?? '';
            $value = $this->parseAttributeValue($attr['value'] ?? []);
            if ($key !== '') {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function parseAttributeValue(array $value): mixed
    {
        if (isset($value['stringValue'])) {
            return $value['stringValue'];
        }
        if (isset($value['intValue'])) {
            return (int) $value['intValue'];
        }
        if (isset($value['doubleValue'])) {
            return (float) $value['doubleValue'];
        }
        if (isset($value['boolValue'])) {
            return (bool) $value['boolValue'];
        }
        if (isset($value['arrayValue'])) {
            return array_map(fn(array $v) => $this->parseAttributeValue($v), $value['arrayValue']['values'] ?? []);
        }
        if (isset($value['kvlistValue'])) {
            return $this->parseAttributes($value['kvlistValue']['values'] ?? []);
        }
        if (isset($value['bytesValue'])) {
            return $value['bytesValue'];
        }
        return null;
    }

    /**
     * @return list<array{name: string, timestamp: float, attributes: array<string, mixed>}>
     */
    private function parseEvents(array $events): array
    {
        $result = [];
        foreach ($events as $event) {
            $result[] = [
                'name' => $event['name'] ?? '',
                'timestamp' => $this->parseTimestamp($event['timeUnixNano'] ?? '0') / 1e9,
                'attributes' => $this->parseAttributes($event['attributes'] ?? []),
            ];
        }
        return $result;
    }

    /**
     * @return list<array{traceId: string, spanId: string, attributes: array<string, mixed>}>
     */
    private function parseLinks(array $links): array
    {
        $result = [];
        foreach ($links as $link) {
            $result[] = [
                'traceId' => $link['traceId'] ?? '',
                'spanId' => $link['spanId'] ?? '',
                'attributes' => $this->parseAttributes($link['attributes'] ?? []),
            ];
        }
        return $result;
    }

    private function parseStatus(array $status): string
    {
        return match ((int) ($status['code'] ?? 0)) {
            0 => 'UNSET',
            1 => 'OK',
            2 => 'ERROR',
            default => 'UNSET',
        };
    }

    private function parseKind(int|string $kind): string
    {
        return match ((int) $kind) {
            0 => 'UNSPECIFIED',
            1 => 'INTERNAL',
            2 => 'SERVER',
            3 => 'CLIENT',
            4 => 'PRODUCER',
            5 => 'CONSUMER',
            default => 'UNSPECIFIED',
        };
    }

    private function parseTimestamp(string|int $nanos): float
    {
        return (float) $nanos;
    }
}

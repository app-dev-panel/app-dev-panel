<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Ingestion\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Collector\OpenTelemetryCollector;
use AppDevPanel\Kernel\Collector\OtlpTraceParser;
use AppDevPanel\Kernel\Collector\SpanRecord;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Storage\StorageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Receives OTLP trace data via HTTP/JSON and stores it as debug entries.
 */
final class OtlpController
{
    private readonly OtlpTraceParser $parser;

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly StorageInterface $storage,
    ) {
        $this->parser = new OtlpTraceParser();
    }

    /**
     * OTLP HTTP/JSON trace receiver.
     *
     * POST /debug/api/otlp/v1/traces
     * Content-Type: application/json
     *
     * Accepts ExportTraceServiceRequest JSON payload.
     */
    public function traces(ServerRequestInterface $request): ResponseInterface
    {
        $body = json_decode($request->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $spans = $this->parser->parse($body);

        if ($spans === []) {
            return $this->responseFactory->createJsonResponse([
                'partialSuccess' => null,
            ]);
        }

        $this->storeSpans($spans);

        return $this->responseFactory->createJsonResponse([
            'partialSuccess' => null,
        ]);
    }

    /**
     * @param list<SpanRecord> $spans
     */
    private function storeSpans(array $spans): void
    {
        // Group spans by traceId for storage
        $grouped = [];
        foreach ($spans as $span) {
            $grouped[$span->traceId][] = $span;
        }

        foreach ($grouped as $traceId => $traceSpans) {
            $idGenerator = new DebuggerIdGenerator();
            $id = $idGenerator->getId();

            $collectorId = OpenTelemetryCollector::class;
            $spanArrays = array_map(static fn(SpanRecord $s) => $s->toArray(), $traceSpans);

            $serviceName = $traceSpans[0]->serviceName;
            $rootSpan = $this->findRootSpan($traceSpans);

            $collectorData = [
                'spans' => $spanArrays,
                'traceCount' => 1,
                'spanCount' => count($traceSpans),
                'errorCount' => count(array_filter($traceSpans, static fn(SpanRecord $s) => $s->status === 'ERROR')),
            ];

            $summary = [
                'id' => $id,
                'collectors' => [
                    ['id' => $collectorId, 'name' => 'Open Telemetry'],
                ],
                'opentelemetry' => [
                    'spans' => count($traceSpans),
                    'traces' => 1,
                    'errors' => $collectorData['errorCount'],
                ],
                'context' => [
                    'type' => 'otlp',
                    'service' => $serviceName,
                    'traceId' => (string) $traceId,
                    'operation' => $rootSpan?->operationName ?? '',
                ],
            ];

            $collectors = [$collectorId => $collectorData];

            $this->storage->write($id, $summary, $collectors, $collectors);
        }
    }

    /**
     * @param list<SpanRecord> $spans
     */
    private function findRootSpan(array $spans): ?SpanRecord
    {
        foreach ($spans as $span) {
            if ($span->parentSpanId === null) {
                return $span;
            }
        }
        return $spans[0] ?? null;
    }
}

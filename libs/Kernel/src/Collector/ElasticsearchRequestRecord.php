<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Immutable record for a completed Elasticsearch request.
 * Used with ElasticsearchCollector::logRequest() for event-based adapters.
 */
final readonly class ElasticsearchRequestRecord
{
    public function __construct(
        public string $method,
        public string $endpoint,
        public string $body,
        public string $line,
        public float $startTime,
        public float $endTime,
        public int $statusCode,
        public string $responseBody = '',
        public int $responseSize = 0,
    ) {}
}

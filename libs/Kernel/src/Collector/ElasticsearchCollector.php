<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use Throwable;

use function array_key_exists;
use function array_sum;
use function array_values;
use function count;

/**
 * Captures Elasticsearch requests from the application.
 *
 * Supports two usage patterns:
 * - Paired: collectRequestStart() → collectRequestEnd()/collectRequestError() (for proxy-based adapters)
 * - Simple: logRequest() (for event-based adapters that measure timing externally)
 */
final class ElasticsearchCollector implements SummaryCollectorInterface
{
    use CollectorTrait;
    use DuplicateDetectionTrait;

    private array $requests = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    /**
     * Start tracking an Elasticsearch request (paired with collectRequestEnd/collectRequestError).
     */
    public function collectRequestStart(string $id, string $method, string $endpoint, string $body, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->requests[$id] = [
            'method' => $method,
            'endpoint' => $endpoint,
            'index' => self::extractIndex($endpoint),
            'body' => $body,
            'line' => $line,
            'status' => 'initialized',
            'startTime' => microtime(true),
            'endTime' => 0.0,
            'duration' => 0.0,
            'statusCode' => 0,
            'responseBody' => '',
            'responseSize' => 0,
            'hitsCount' => null,
            'exception' => null,
        ];
    }

    /**
     * Mark a tracked request as completed.
     */
    public function collectRequestEnd(string $id, int $statusCode, string $responseBody, int $responseSize): void
    {
        if (!$this->isActive() || !array_key_exists($id, $this->requests)) {
            return;
        }

        $endTime = microtime(true);
        $this->requests[$id]['status'] = $statusCode >= 400 ? 'error' : 'success';
        $this->requests[$id]['statusCode'] = $statusCode;
        $this->requests[$id]['responseBody'] = $responseBody;
        $this->requests[$id]['responseSize'] = $responseSize;
        $this->requests[$id]['endTime'] = $endTime;
        $this->requests[$id]['duration'] = $endTime - $this->requests[$id]['startTime'];
        $this->requests[$id]['hitsCount'] = self::extractHitsCount($responseBody);

        $this->timelineCollector->collect($this, count($this->requests));
    }

    /**
     * Mark a tracked request as failed.
     */
    public function collectRequestError(string $id, Throwable $exception): void
    {
        if (!$this->isActive() || !array_key_exists($id, $this->requests)) {
            return;
        }

        $endTime = microtime(true);
        $this->requests[$id]['status'] = 'error';
        $this->requests[$id]['exception'] = $exception;
        $this->requests[$id]['endTime'] = $endTime;
        $this->requests[$id]['duration'] = $endTime - $this->requests[$id]['startTime'];

        $this->timelineCollector->collect($this, count($this->requests));
    }

    /**
     * Log a completed request in one call (for adapters that measure timing externally).
     */
    public function logRequest(ElasticsearchRequestRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->requests[] = [
            'method' => $record->method,
            'endpoint' => $record->endpoint,
            'index' => self::extractIndex($record->endpoint),
            'body' => $record->body,
            'line' => $record->line,
            'status' => $record->statusCode >= 400 ? 'error' : 'success',
            'startTime' => $record->startTime,
            'endTime' => $record->endTime,
            'duration' => $record->endTime - $record->startTime,
            'statusCode' => $record->statusCode,
            'responseBody' => $record->responseBody,
            'responseSize' => $record->responseSize,
            'hitsCount' => self::extractHitsCount($record->responseBody),
            'exception' => null,
        ];

        $this->timelineCollector->collect($this, count($this->requests));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $requests = array_values($this->requests);

        return [
            'requests' => $requests,
            'duplicates' => $this->detectDuplicates(
                $requests,
                static fn(array $request) => $request['method'] . ' ' . $request['endpoint'],
            ),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        $requests = array_values($this->requests);
        $duplicates = $this->detectDuplicates(
            $requests,
            static fn(array $request) => $request['method'] . ' ' . $request['endpoint'],
        );

        return [
            'elasticsearch' => [
                'total' => count($this->requests),
                'errors' => count(array_filter(
                    $this->requests,
                    static fn(array $request) => $request['status'] === 'error',
                )),
                'totalTime' => array_sum(array_map(static fn(array $request) => $request['duration'], $this->requests)),
                'duplicateGroups' => count($duplicates['groups']),
                'totalDuplicatedCount' => $duplicates['totalDuplicatedCount'],
            ],
        ];
    }

    protected function reset(): void
    {
        $this->requests = [];
    }

    /**
     * Extract index name from Elasticsearch endpoint path.
     *
     * Handles patterns like: /index/_search, /index/_doc/1, /index, /_cat/indices
     */
    private static function extractIndex(string $endpoint): string
    {
        $path = ltrim($endpoint, '/');
        if ($path === '') {
            return '';
        }

        $segments = explode('/', $path);
        $first = $segments[0];

        // Skip cluster/system endpoints that start with _
        if (str_starts_with($first, '_')) {
            return '';
        }

        return $first;
    }

    private static function extractHitsCount(string $responseBody): ?int
    {
        if ($responseBody === '') {
            return null;
        }

        $decoded = json_decode($responseBody, true);
        if (!is_array($decoded)) {
            return null;
        }

        if (isset($decoded['hits']['total']['value'])) {
            return (int) $decoded['hits']['total']['value'];
        }

        if (isset($decoded['hits']['total']) && is_int($decoded['hits']['total'])) {
            return $decoded['hits']['total'];
        }

        return null;
    }
}

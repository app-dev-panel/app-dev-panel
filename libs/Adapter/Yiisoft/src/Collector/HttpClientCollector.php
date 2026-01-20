<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use GuzzleHttp\Psr7\Message;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use function count;

/**
 * @psalm-type RequestEntry = array{
 *     startTime: float,
 *     endTime: float,
 *     totalTime: float,
 *     method: string,
 *     uri: string,
 *     headers: string[][],
 *     line: string,
 *     responseRaw?: string,
 *     responseStatus?: int,
 * }
 */
final class HttpClientCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * @psalm-var array<string, non-empty-list<RequestEntry>>
     */
    private array $requests = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector
    ) {
    }

    public function collect(RequestInterface $request, float $startTime, string $line, string $uniqueId): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->requests[$uniqueId][] = [
            'startTime' => $startTime,
            'endTime' => $startTime,
            'totalTime' => 0.0,
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $request->getHeaders(),
            'line' => $line,
        ];
        $this->timelineCollector->collect($this, $uniqueId);
    }

    public function collectTotalTime(?ResponseInterface $response, float $endTime, ?string $uniqueId): void
    {
        if (!$this->isActive()) {
            return;
        }

        if (!isset($this->requests[$uniqueId])) {
            return;
        }
        /** @psalm-suppress UnsupportedReferenceUsage */
        $entry = &$this->requests[$uniqueId][count($this->requests[$uniqueId]) - 1];
        if ($response instanceof ResponseInterface) {
            $entry['responseRaw'] = Message::toString($response);
            $entry['responseStatus'] = $response->getStatusCode();
            Message::rewindBody($response);
        }
        $entry['endTime'] = $endTime;
        $entry['totalTime'] = $entry['endTime'] - $entry['startTime'];
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return array_merge(...array_values($this->requests));
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'http' => [
                'count' => array_sum(array_map(static fn (array $requests) => count($requests), $this->requests)),
                'totalTime' => array_sum(
                    array_merge(
                        ...array_map(
                            static fn (array $entry) => array_column($entry, 'totalTime'),
                            array_values($this->requests)
                        )
                    )
                ),
            ],
        ];
    }
}

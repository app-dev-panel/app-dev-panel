<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

/**
 * Captures Redis commands (GET, SET, DEL, etc.) across any Redis client.
 *
 * Framework adapters call logCommand() with normalized data.
 * Tracks command counts, timing, and errors per connection.
 */
final class RedisCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{connection: string, command: string, arguments: array, result: mixed, duration: float, error: ?string, line: string}> */
    private array $commands = [];
    private float $totalTime = 0.0;
    private int $errorCount = 0;
    /** @var array<string, true> */
    private array $connections = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector,
    ) {}

    public function logCommand(RedisCommandRecord $record): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->commands[] = $record->toArray();
        $this->totalTime += $record->duration;
        $this->connections[$record->connection] = true;

        if ($record->error !== null) {
            ++$this->errorCount;
        }

        $this->timelineCollector->collect($this, count($this->commands));
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'commands' => $this->commands,
            'totalTime' => $this->totalTime,
            'errorCount' => $this->errorCount,
            'totalCommands' => count($this->commands),
            'connections' => array_keys($this->connections),
        ];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'redis' => [
                'commandCount' => count($this->commands),
                'errorCount' => $this->errorCount,
                'totalTime' => $this->totalTime,
            ],
        ];
    }

    protected function reset(): void
    {
        $this->commands = [];
        $this->totalTime = 0.0;
        $this->errorCount = 0;
        $this->connections = [];
    }
}

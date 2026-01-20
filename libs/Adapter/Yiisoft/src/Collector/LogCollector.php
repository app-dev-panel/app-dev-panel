<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

class LogCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timelineCollector
    ) {
    }

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return $this->messages;
    }

    public function collect(string $level, mixed $message, array $context, string $line): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->messages[] = [
            'time' => microtime(true),
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'line' => $line,
        ];
        $this->timelineCollector->collect($this, count($this->messages));
    }

    private function reset(): void
    {
        $this->messages = [];
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }
        return [
            'logger' => [
                'total' => count($this->messages),
            ],
        ];
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\CollectorTrait;
use AppDevPanel\Kernel\Collector\TimelineCollector;

/**
 * Captures Yii 2 log messages from the Logger component.
 *
 * Yii 2 uses its own logging system (yii\log\Logger) instead of PSR-3.
 * This collector reads log messages from Yii::getLogger() at shutdown time,
 * since Yii 2's logger buffers all messages in memory.
 */
final class Yii2LogCollector implements CollectorInterface
{
    use CollectorTrait;

    /** @var array<int, array{level: string, message: string, category: string, timestamp: float}> */
    private array $messages = [];

    public function __construct(
        private readonly TimelineCollector $timeline,
    ) {}

    public function shutdown(): void
    {
        if ($this->isActive()) {
            $this->collectFromYiiLogger();
        }

        $this->reset();
        // Mark inactive *after* collecting
    }

    public function getCollected(): array
    {
        // Collect right before returning, in case shutdown wasn't called yet
        if ($this->messages === [] && $this->isActive()) {
            $this->collectFromYiiLogger();
        }

        return [
            'messages' => $this->messages,
            'count' => count($this->messages),
        ];
    }

    private function collectFromYiiLogger(): void
    {
        $logger = \Yii::getLogger();
        if ($logger === null) {
            return;
        }

        $logger->flush(true);

        foreach ($logger->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            $levelName = match ($level) {
                \yii\log\Logger::LEVEL_ERROR => 'error',
                \yii\log\Logger::LEVEL_WARNING => 'warning',
                \yii\log\Logger::LEVEL_INFO => 'info',
                \yii\log\Logger::LEVEL_TRACE => 'trace',
                \yii\log\Logger::LEVEL_PROFILE => 'profile',
                \yii\log\Logger::LEVEL_PROFILE_BEGIN => 'profile_begin',
                \yii\log\Logger::LEVEL_PROFILE_END => 'profile_end',
                default => 'unknown',
            };

            $messageText = is_string($text) ? $text : print_r($text, true);

            $this->messages[] = [
                'level' => $levelName,
                'message' => $messageText,
                'category' => $category,
                'timestamp' => $timestamp,
            ];

            if (in_array($levelName, ['error', 'warning', 'info'], true)) {
                $this->timeline->addEvent('yii-log', "[$levelName] $category", [
                    'message' => mb_substr($messageText, 0, 100),
                ]);
            }
        }
    }

    protected function reset(): void
    {
        $this->messages = [];
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\LogCollector;
use yii\log\Logger;
use yii\log\Target;

/**
 * Real-time log target that feeds Yii 2 log messages to ADP's LogCollector.
 *
 * Registered as a log target in Yii::getLogger()->targets[].
 * Unlike Yii2LogCollector (which reads logger messages at shutdown),
 * this target captures messages in real-time as they are flushed by the logger,
 * preventing loss of messages that were flushed early.
 */
final class DebugLogTarget extends Target
{
    public function __construct(
        private readonly LogCollector $logCollector,
    ) {
        parent::__construct();

        // Export every message immediately (no batching)
        $this->exportInterval = 1;

        // Capture all levels
        $this->setLevels(Logger::LEVEL_ERROR | Logger::LEVEL_WARNING | Logger::LEVEL_INFO | Logger::LEVEL_TRACE);

        // Include profiling
        $this->logVars = [];
    }

    /**
     * Called by the Yii logger when messages are flushed.
     * Feeds each message to the Kernel's LogCollector.
     */
    public function export(): void
    {
        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            $levelName = self::mapLevel($level);
            $messageText = is_string($text) ? $text : print_r($text, true);

            $this->logCollector->collect(
                $levelName,
                $messageText,
                ['category' => $category],
                '', // line - Yii2 doesn't provide caller file:line per message
            );
        }
    }

    private static function mapLevel(int $level): string
    {
        return match ($level) {
            Logger::LEVEL_ERROR => 'error',
            Logger::LEVEL_WARNING => 'warning',
            Logger::LEVEL_INFO => 'info',
            Logger::LEVEL_TRACE => 'debug',
            Logger::LEVEL_PROFILE, Logger::LEVEL_PROFILE_BEGIN, Logger::LEVEL_PROFILE_END => 'debug',
            default => 'info',
        };
    }
}

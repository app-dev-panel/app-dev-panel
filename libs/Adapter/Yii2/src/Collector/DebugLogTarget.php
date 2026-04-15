<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\LogCollector;
use yii\helpers\VarDumper;
use yii\log\Logger;
use yii\log\Target;

/**
 * Real-time log target that feeds Yii 2 log messages to ADP's LogCollector.
 *
 * Registered as a log target in Yii::getLogger()->targets[].
 * Captures messages in real-time as they are flushed by the logger,
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

            [$normalizedMessage, $extraContext] = self::normalizeMessage($text);
            $context = ['category' => $category] + $extraContext;

            $this->logCollector->collect($levelName, $normalizedMessage, $context, ''); // line - Yii2 doesn't provide caller file:line per message
        }
    }

    /**
     * Normalize the Yii 2 log `$text` value into a string `$message` + extra context.
     *
     * Yii's logger accepts arbitrary values (e.g., UrlManager::parseRequest passes an
     * associative array). Downstream consumers (SearchLogsTool) concatenate the message
     * with `. ' ' . json_encode(...)`, which fatally throws "Array to string conversion"
     * when the message is an array. Normalize at the producer side.
     *
     * @return array{0: string, 1: array<string, mixed>}
     */
    private static function normalizeMessage(mixed $text): array
    {
        if ($text instanceof \Throwable) {
            return [
                $text->getMessage(),
                [
                    'exception' => $text->getMessage(),
                    'class' => $text::class,
                    'trace' => $text->getTraceAsString(),
                ],
            ];
        }

        if ($text === null || is_scalar($text)) {
            return [(string) $text, []];
        }

        if ($text instanceof \Stringable) {
            return [(string) $text, []];
        }

        // Array or object: dump for readability, preserve original in raw_message
        $dumped = VarDumper::dumpAsString($text, 10, false);

        return [$dumped, ['raw_message' => $text]];
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

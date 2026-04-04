<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\DebugServer\Broadcaster;
use AppDevPanel\Kernel\DebugServer\Connection;
use yii\log\Logger;
use yii\log\Target;
use Yiisoft\VarDumper\VarDumper;

/**
 * Log target that broadcasts log messages via UDP for the Live Feed.
 *
 * Each message is sent as a MESSAGE_TYPE_LOGGER datagram so that
 * SSE listeners display it in real-time.
 */
final class BroadcastingLogTarget extends Target
{
    private readonly Broadcaster $broadcaster;

    public function __construct()
    {
        parent::__construct();

        $this->broadcaster = new Broadcaster();
        $this->exportInterval = 1;
        $this->setLevels(Logger::LEVEL_ERROR | Logger::LEVEL_WARNING | Logger::LEVEL_INFO | Logger::LEVEL_TRACE);
        $this->logVars = [];
    }

    public function export(): void
    {
        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            try {
                $this->broadcaster->broadcast(Connection::MESSAGE_TYPE_LOGGER, VarDumper::create([
                    'level' => self::mapLevel($level),
                    'message' => (string) $text,
                    'context' => ['category' => $category],
                ])->asJson(false, 1));
            } catch (\Throwable) {
                // Never let broadcast failure break the app
            }
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

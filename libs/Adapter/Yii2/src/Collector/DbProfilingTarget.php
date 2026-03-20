<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Collector;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use yii\log\Logger;
use yii\log\Target;

/**
 * Log target that intercepts Yii 2 DB profiling messages to feed DatabaseCollector.
 *
 * Yii 2's Command class uses Yii::beginProfile()/endProfile() for query profiling,
 * which writes LEVEL_PROFILE_BEGIN and LEVEL_PROFILE_END messages to the Logger.
 * This target captures those messages in real-time (exportInterval=1) and feeds
 * the DatabaseCollector with query SQL, timing, and metadata.
 *
 * Categories captured: 'yii\db\Command::execute', 'yii\db\Command::query'.
 */
final class DbProfilingTarget extends Target
{
    /** @var array<string, float> Active query timers indexed by SQL */
    private array $activeQueries = [];

    public function __construct(
        private readonly DatabaseCollector $dbCollector,
    ) {
        parent::__construct();

        // Export every message immediately
        $this->exportInterval = 1;

        // Only capture profiling messages
        $this->setLevels(Logger::LEVEL_PROFILE | Logger::LEVEL_PROFILE_BEGIN | Logger::LEVEL_PROFILE_END);

        // Only capture DB-related categories
        $this->categories = ['yii\db\Command::execute', 'yii\db\Command::query'];

        $this->logVars = [];
    }

    public function export(): void
    {
        foreach ($this->messages as $message) {
            [$text, $level, $category, $timestamp] = $message;

            $sql = is_string($text) ? $text : (string) $text;

            if ($level === Logger::LEVEL_PROFILE_BEGIN) {
                $this->activeQueries[$sql] = (float) $timestamp;
            } elseif ($level === Logger::LEVEL_PROFILE_END) {
                $startTime = $this->activeQueries[$sql] ?? (float) $timestamp;
                $endTime = (float) $timestamp;
                unset($this->activeQueries[$sql]);

                $this->dbCollector->logQuery($sql, $sql, [], '', $startTime, $endTime, 0);
            }
        }
    }
}

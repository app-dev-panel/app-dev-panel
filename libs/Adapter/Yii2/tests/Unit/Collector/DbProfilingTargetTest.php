<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Collector;

use AppDevPanel\Adapter\Yii2\Collector\DbProfilingTarget;
use AppDevPanel\Kernel\Collector\DatabaseCollector;
use AppDevPanel\Kernel\Collector\TimelineCollector;
use PHPUnit\Framework\TestCase;
use yii\log\Logger;

final class DbProfilingTargetTest extends TestCase
{
    public function testExportCapturesCompletedQuery(): void
    {
        [$dbCollector, $target] = $this->createTarget();

        $startTime = microtime(true);
        $endTime = $startTime + 0.05;

        $target->messages = [
            ['SELECT * FROM users', Logger::LEVEL_PROFILE_BEGIN, 'yii\db\Command::query', $startTime],
            ['SELECT * FROM users', Logger::LEVEL_PROFILE_END,   'yii\db\Command::query', $endTime],
        ];

        $target->export();

        $collected = $dbCollector->getCollected();
        $this->assertCount(1, $collected['queries']);
        $this->assertSame('SELECT * FROM users', $collected['queries'][0]['sql']);
    }

    public function testExportIgnoresUnpairedBegin(): void
    {
        [$dbCollector, $target] = $this->createTarget();

        $target->messages = [
            ['SELECT 1', Logger::LEVEL_PROFILE_BEGIN, 'yii\db\Command::query', microtime(true)],
        ];

        $target->export();

        $collected = $dbCollector->getCollected();
        $this->assertCount(0, $collected['queries']);
    }

    public function testExportHandlesEndWithoutBegin(): void
    {
        [$dbCollector, $target] = $this->createTarget();

        $timestamp = microtime(true);
        $target->messages = [
            ['SELECT 1', Logger::LEVEL_PROFILE_END, 'yii\db\Command::query', $timestamp],
        ];

        $target->export();

        $collected = $dbCollector->getCollected();
        $this->assertCount(1, $collected['queries']);
    }

    public function testExportHandlesMultipleQueries(): void
    {
        [$dbCollector, $target] = $this->createTarget();

        $t1 = microtime(true);
        $t2 = $t1 + 0.01;
        $t3 = $t1 + 0.02;
        $t4 = $t1 + 0.03;

        $target->messages = [
            ['SELECT * FROM users', Logger::LEVEL_PROFILE_BEGIN, 'yii\db\Command::query',   $t1],
            ['SELECT * FROM users', Logger::LEVEL_PROFILE_END,   'yii\db\Command::query',   $t2],
            ['INSERT INTO log',     Logger::LEVEL_PROFILE_BEGIN, 'yii\db\Command::execute', $t3],
            ['INSERT INTO log',     Logger::LEVEL_PROFILE_END,   'yii\db\Command::execute', $t4],
        ];

        $target->export();

        $collected = $dbCollector->getCollected();
        $this->assertCount(2, $collected['queries']);
        $this->assertSame('SELECT * FROM users', $collected['queries'][0]['sql']);
        $this->assertSame('INSERT INTO log', $collected['queries'][1]['sql']);
    }

    public function testConstructorSetsCorrectConfiguration(): void
    {
        [, $target] = $this->createTarget();

        $this->assertSame(1, $target->exportInterval);
        $this->assertSame(['yii\db\Command::execute', 'yii\db\Command::query'], $target->categories);
        $this->assertSame([], $target->logVars);
    }

    /**
     * @return array{DatabaseCollector, DbProfilingTarget}
     */
    private function createTarget(): array
    {
        $timeline = new TimelineCollector();
        $timeline->startup();
        $dbCollector = new DatabaseCollector($timeline);
        $dbCollector->startup();

        return [$dbCollector, new DbProfilingTarget($dbCollector)];
    }
}

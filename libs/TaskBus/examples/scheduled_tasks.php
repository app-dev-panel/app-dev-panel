<?php

/**
 * Scheduled tasks example — create cron-based recurring tasks.
 *
 * Run: php examples/scheduled_tasks.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use AppDevPanel\TaskBus\Scheduler\CronExpression;
use AppDevPanel\TaskBus\Scheduler\ScheduleRegistry;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;

$config = new TaskBusConfig(databasePath: ':memory:');
$pdo = PdoFactory::createInMemory();
$registry = new ScheduleRegistry($pdo);
$bus = TaskBusFactory::create($config);

echo "=== TaskBus Scheduler ===\n\n";

// 1. Create schedules
echo "1. Creating schedules...\n";
$registry->create(
    name: 'run-tests-nightly',
    cron: '0 2 * * *',
    messageType: 'run_tests',
    messagePayload: ['runner' => 'vendor/bin/phpunit', 'args' => ['--testsuite', 'Kernel']],
);

$registry->create(
    name: 'analyze-every-hour',
    cron: '0 * * * *',
    messageType: 'run_analyzer',
    messagePayload: ['analyzer' => 'vendor/bin/mago', 'args' => ['analyze']],
);

$registry->create(
    name: 'health-check-every-5min',
    cron: '*/5 * * * *',
    messageType: 'run_command',
    messagePayload: ['command' => 'curl -sf http://localhost:8080/health || echo "DOWN"'],
);

// 2. List schedules
echo "\n2. All schedules:\n";
foreach ($registry->list() as $schedule) {
    $status = $schedule['enabled'] ? 'enabled' : 'disabled';
    echo "   [{$status}] {$schedule['name']} — {$schedule['cron']} (next: {$schedule['next_run_at']})\n";
}

// 3. Cron expression examples
echo "\n3. Cron expression examples:\n";
$examples = [
    '* * * * *' => 'Every minute',
    '*/5 * * * *' => 'Every 5 minutes',
    '0 * * * *' => 'Every hour',
    '0 2 * * *' => 'Daily at 2:00 AM',
    '0 9-17 * * 1-5' => 'Hourly during business hours (Mon-Fri)',
    '0 0 1,15 * *' => '1st and 15th of each month at midnight',
];

$now = new DateTimeImmutable();
foreach ($examples as $expr => $description) {
    $cron = new CronExpression($expr);
    $next = $cron->nextRunAfter($now);
    echo "   {$expr} — {$description}\n";
    echo "     Next: {$next->format('Y-m-d H:i')}\n";
}

echo "\nDone!\n";

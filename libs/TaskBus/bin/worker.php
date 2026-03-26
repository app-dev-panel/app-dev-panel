<?php

/**
 * TaskBus async worker — polls SQLite for pending tasks and dispatches them.
 *
 * This is a simple polling worker for environments where Symfony Messenger's
 * built-in transport/worker is not used. It pulls pending tasks from SQLite
 * and dispatches them through the bus synchronously.
 *
 * Usage: php bin/worker.php
 * Environment:
 *   TASK_BUS_DB_PATH        — SQLite database path (default: data/task-bus.sqlite)
 *   TASK_BUS_MAX_CONCURRENT — Max concurrent tasks (default: 4)
 *   TASK_BUS_POLL_INTERVAL  — Poll interval in microseconds (default: 200000 = 200ms)
 */

declare(strict_types=1);
declare(ticks=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use AppDevPanel\TaskBus\Message\RunAnalyzer;
use AppDevPanel\TaskBus\Message\RunCommand;
use AppDevPanel\TaskBus\Message\RunTests;
use AppDevPanel\TaskBus\Scheduler\ScheduleRegistry;
use AppDevPanel\TaskBus\Storage\PdoFactory;
use AppDevPanel\TaskBus\Storage\SqliteTaskRepository;
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;

$dbPath = getenv('TASK_BUS_DB_PATH') ?: __DIR__ . '/../../../data/task-bus.sqlite';
$maxConcurrent = (int) (getenv('TASK_BUS_MAX_CONCURRENT') ?: 4);
$pollInterval = (int) (getenv('TASK_BUS_POLL_INTERVAL') ?: 200_000);

$config = new TaskBusConfig(
    databasePath: $dbPath,
    maxConcurrentTasks: $maxConcurrent,
    workerSleepInterval: $pollInterval,
);

$pdo = PdoFactory::create($config->databasePath);
$repository = new SqliteTaskRepository($pdo);
$bus = TaskBusFactory::create($config, $repository);
$scheduleRegistry = new ScheduleRegistry($pdo);

$running = true;
pcntl_signal(SIGINT, static function () use (&$running): void {
    echo "\nShutting down worker...\n";
    $running = false;
});
pcntl_signal(SIGTERM, static function () use (&$running): void {
    echo "\nShutting down worker...\n";
    $running = false;
});

echo "TaskBus worker started (db: {$dbPath}, max: {$maxConcurrent}, poll: {$pollInterval}μs)\n";
echo "Press Ctrl+C to stop.\n\n";

$lastScheduleCheck = 0;

while ($running) {
    // Check scheduled tasks every 60 seconds
    $now = time();
    if ($now - $lastScheduleCheck >= 60) {
        $lastScheduleCheck = $now;
        $dueSchedules = $scheduleRegistry->findDue();
        foreach ($dueSchedules as $schedule) {
            echo "[scheduler] Dispatching: {$schedule['name']} ({$schedule['message_type']})\n";

            $params = $schedule['message_payload'];
            match ($schedule['message_type']) {
                'run_command' => $bus->runCommand($params['command'] ?? 'echo "no command"'),
                'run_tests' => $bus->runTests($params['runner'] ?? 'vendor/bin/phpunit', $params['args'] ?? []),
                'run_analyzer' => $bus->runAnalyzer($params['analyzer'] ?? 'vendor/bin/mago', $params['args'] ?? []),
                default => null,
            };
        }
    }

    // Poll for pending tasks (scheduled tasks that became ready)
    $scheduledReady = $repository->findScheduledReady();
    foreach ($scheduledReady as $task) {
        echo "[worker] Processing scheduled task: {$task->id} ({$task->type})\n";
        $payload = $task->payload;

        match ($task->type) {
            'run_command' => $bus->dispatch(new RunCommand(
                taskId: $task->id,
                command: $payload['command'] ?? 'echo "no command"',
                workingDirectory: $payload['working_directory'] ?? null,
            )),
            'run_tests' => $bus->dispatch(new RunTests(
                taskId: $task->id,
                runner: $payload['runner'] ?? 'vendor/bin/phpunit',
                args: $payload['args'] ?? [],
                workingDirectory: $payload['working_directory'] ?? null,
            )),
            'run_analyzer' => $bus->dispatch(new RunAnalyzer(
                taskId: $task->id,
                analyzer: $payload['analyzer'] ?? 'vendor/bin/mago',
                args: $payload['args'] ?? [],
                workingDirectory: $payload['working_directory'] ?? null,
            )),
            default => null,
        };
    }

    usleep($pollInterval);
}

echo "Worker stopped.\n";

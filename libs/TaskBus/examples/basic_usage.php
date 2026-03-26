<?php

/**
 * Basic TaskBus usage — synchronous dispatch.
 *
 * Run: php examples/basic_usage.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;

// Create a TaskBus with SQLite storage in the current directory
$bus = TaskBusFactory::create();

echo "=== TaskBus Basic Usage ===\n\n";

// 1. Run a simple command
echo "1. Running a command...\n";
$taskId = $bus->runCommand('echo "Hello from TaskBus!"', workingDirectory: '/tmp');
$task = $bus->status($taskId);
echo "   Status: {$task->status->value}\n";
echo "   Output: {$task->result['stdout']}\n\n";

// 2. Run tests
echo "2. Running tests...\n";
$taskId = $bus->runTests(
    runner: 'vendor/bin/phpunit',
    args: ['--testsuite', 'Kernel', '--no-progress'],
    workingDirectory: dirname(__DIR__, 3),
    priority: TaskPriority::High,
);
$task = $bus->status($taskId);
echo "   Status: {$task->status->value}\n";
echo "   Duration: {$task->result['duration']}s\n\n";

// 3. Run static analyzer
echo "3. Running analyzer...\n";
$taskId = $bus->runAnalyzer(
    analyzer: 'vendor/bin/mago',
    args: ['analyze'],
    workingDirectory: dirname(__DIR__, 3),
);
$task = $bus->status($taskId);
echo "   Status: {$task->status->value}\n\n";

// 4. Schedule a command for the future
echo "4. Scheduling a command...\n";
$taskId = $bus->scheduleCommand(
    command: 'echo "Deferred execution"',
    scheduledAt: new DateTimeImmutable('+5 minutes'),
);
$task = $bus->status($taskId);
echo "   Status: {$task->status->value}\n";
echo "   Scheduled at: {$task->scheduledAt->format('H:i:s')}\n\n";

// 5. Cancel the scheduled task
echo "5. Cancelling scheduled task...\n";
$cancelled = $bus->cancel($taskId);
echo "   Cancelled: " . ($cancelled ? 'yes' : 'no') . "\n\n";

// 6. List all tasks
echo "6. Task summary:\n";
echo "   Total: {$bus->count()}\n";
echo "   Completed: {$bus->count(TaskStatus::Completed)}\n";
echo "   Failed: {$bus->count(TaskStatus::Failed)}\n";
echo "   Cancelled: {$bus->count(TaskStatus::Cancelled)}\n\n";

// 7. View logs for a task
$tasks = $bus->list(TaskStatus::Completed, limit: 1);
if ($tasks !== []) {
    $logs = $bus->logs($tasks[0]->id);
    echo "7. Logs for task {$tasks[0]->id}:\n";
    foreach ($logs as $log) {
        echo "   [{$log->level}] {$log->message}\n";
    }
}

echo "\nDone!\n";

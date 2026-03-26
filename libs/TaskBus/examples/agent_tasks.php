<?php

/**
 * LLM Agent task submission example.
 *
 * Demonstrates how an LLM agent (e.g. via MCP) would submit tasks to the bus.
 *
 * Run: php examples/agent_tasks.php
 */

declare(strict_types=1);

require_once __DIR__ . '/../../../vendor/autoload.php';

use AppDevPanel\TaskBus\Process\ProcessResult;
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\TaskBusFactory;
use AppDevPanel\TaskBus\TaskPriority;

// Simulate a process runner that captures output
$mockRunner = new class () implements ProcessRunnerInterface {
    public function run(string $command, ?string $workingDirectory = null, array $env = [], ?int $timeout = null): ProcessResult
    {
        echo "   [exec] {$command}\n";
        return new ProcessResult(exitCode: 0, stdout: "Simulated output for: {$command}", stderr: '', duration: 0.5);
    }
};

$bus = TaskBusFactory::createInMemory(
    processRunner: $mockRunner,
    agentActions: [
        'fix_formatting' => 'vendor/bin/mago fmt',
        'run_lint' => 'vendor/bin/mago lint',
        'run_tests' => 'vendor/bin/phpunit --testsuite Kernel',
        'generate_docs' => 'php bin/generate-docs.php',
    ],
);

echo "=== LLM Agent Task Submission ===\n\n";

// Agent submits a formatting fix
echo "1. Agent: fix code formatting\n";
$taskId = $bus->submitAgentTask(
    action: 'fix_formatting',
    parameters: ['reason' => 'Code style violations found during review'],
    priority: TaskPriority::High,
);
$task = $bus->status($taskId);
echo "   Result: {$task->status->value}\n\n";

// Agent submits a lint check
echo "2. Agent: run linter\n";
$taskId = $bus->submitAgentTask(action: 'run_lint');
$task = $bus->status($taskId);
echo "   Result: {$task->status->value}\n\n";

// Agent submits tests after fixing code
echo "3. Agent: verify with tests\n";
$taskId = $bus->submitAgentTask(
    action: 'run_tests',
    parameters: ['context' => 'Post-fix verification'],
);
$task = $bus->status($taskId);
echo "   Result: {$task->status->value}\n\n";

// Agent tries an unknown action
echo "4. Agent: unknown action (should fail)\n";
$taskId = $bus->submitAgentTask(action: 'deploy_to_production');
$task = $bus->status($taskId);
echo "   Result: {$task->status->value}\n";
echo "   Error: {$task->error['error']}\n\n";

// Summary
echo "5. All tasks:\n";
foreach ($bus->list() as $task) {
    $duration = $task->result['duration'] ?? '-';
    echo "   [{$task->status->value}] {$task->type} ({$task->createdBy}) — {$duration}s\n";
}

echo "\nDone!\n";

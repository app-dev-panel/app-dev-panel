# TaskBus — Integration Guide

## Quick Start

### PHP API (in-process)

```php
use AppDevPanel\TaskBus\TaskBusFactory;

// Create with SQLite file storage
$bus = TaskBusFactory::create();

// Or in-memory (for tests)
$bus = TaskBusFactory::createInMemory();
```

All methods below are synchronous by default — the task executes immediately and the call returns after completion.

### Run a shell command

```php
$taskId = $bus->runCommand('composer install --no-dev');

$task = $bus->status($taskId);
// $task->status === TaskStatus::Completed
// $task->result === ['exit_code' => 0, 'stdout' => '...', 'stderr' => '', 'duration' => 4.2, 'success' => true]
```

With options:

```php
$taskId = $bus->runCommand(
    command: 'npm run build',
    workingDirectory: '/app/frontend',
    env: ['NODE_ENV' => 'production'],
    priority: TaskPriority::High,
    timeout: 120,
    createdBy: 'deploy-script',
);
```

### Run tests

```php
$taskId = $bus->runTests(
    runner: 'vendor/bin/phpunit',
    args: ['--testsuite', 'Kernel', '--no-progress'],
    workingDirectory: '/app',
    timeout: 600,
);
```

### Run static analyzer

```php
$taskId = $bus->runAnalyzer(
    analyzer: 'vendor/bin/mago',
    args: ['analyze', '--no-progress'],
    workingDirectory: '/app',
);
```

### Submit an LLM agent task

Agent tasks map action names to shell commands. Register the mapping at creation time:

```php
$bus = TaskBusFactory::create(
    agentActions: [
        'fix_formatting' => 'vendor/bin/mago fmt',
        'run_lint' => 'vendor/bin/mago lint',
        'run_tests' => 'vendor/bin/phpunit --testsuite Kernel',
    ],
);

$taskId = $bus->submitAgentTask(
    action: 'fix_formatting',
    parameters: ['reason' => 'Style violations found'],
    priority: TaskPriority::High,
);
```

Unknown actions fail immediately with an error in `$task->error`.

### Schedule a deferred command

```php
$taskId = $bus->scheduleCommand(
    command: 'php bin/cleanup.php',
    scheduledAt: new DateTimeImmutable('+30 minutes'),
    priority: TaskPriority::Low,
);
// $task->status === TaskStatus::Scheduled
// Requires a running worker to execute when the time comes.
```

### Cancel a task

```php
$bus->cancel($taskId); // Returns true if cancelled, false if already terminal
```

### Query tasks

```php
// Get task details
$task = $bus->status($taskId); // ?Task — null if not found

// List all tasks
$tasks = $bus->list(limit: 20, offset: 0);

// List by status
$failed = $bus->list(status: TaskStatus::Failed);

// Count
$total = $bus->count();
$pending = $bus->count(TaskStatus::Pending);

// Task logs
$logs = $bus->logs($taskId);
foreach ($logs as $log) {
    echo "[{$log->level}] {$log->message}\n";
}
```

---

## JSON-RPC API (remote)

The TaskBus exposes a JSON-RPC 2.0 server over TCP or Unix socket. This is the primary interface for external clients (panel frontend, LLM agents, CLI tools).

### Start the server

```bash
# TCP (default)
php libs/TaskBus/examples/json_rpc_server.php tcp://127.0.0.1:9800

# Unix socket
php libs/TaskBus/examples/json_rpc_server.php unix:///tmp/task-bus.sock
```

Or via Docker:

```bash
cd libs/TaskBus
docker compose up -d
```

### Protocol

- Transport: TCP stream, newline-delimited JSON
- Encoding: JSON-RPC 2.0 (request `{jsonrpc, method, params, id}`, response `{jsonrpc, result|error, id}`)
- Batch: send a JSON array of requests, receive a JSON array of responses
- Notifications: omit `id` — server processes but returns no response

### Connect from any language

```bash
# netcat
echo '{"jsonrpc":"2.0","method":"task.submit","params":{"type":"run_command","command":"echo hi"},"id":1}' | nc localhost 9800

# socat (for Unix socket)
echo '{"jsonrpc":"2.0","method":"task.list","params":{},"id":1}' | socat - UNIX-CONNECT:/tmp/task-bus.sock
```

```python
# Python
import socket, json

sock = socket.create_connection(("127.0.0.1", 9800))
request = {"jsonrpc": "2.0", "method": "task.list", "params": {}, "id": 1}
sock.sendall(json.dumps(request).encode() + b"\n")
response = json.loads(sock.recv(65536))
print(response["result"]["tasks"])
sock.close()
```

```typescript
// TypeScript (Node.js)
import net from 'net';

const client = net.createConnection({ port: 9800 }, () => {
    client.write(JSON.stringify({
        jsonrpc: '2.0',
        method: 'task.submit',
        params: { type: 'run_command', command: 'echo hello' },
        id: 1,
    }) + '\n');
});

client.on('data', (data) => {
    const response = JSON.parse(data.toString());
    console.log(response.result); // { task_id: "..." }
    client.end();
});
```

### Methods Reference

#### task.submit

Submit a new task for immediate execution.

```json
// Request
{
    "jsonrpc": "2.0",
    "method": "task.submit",
    "params": {
        "type": "run_command",
        "command": "echo hello world",
        "working_directory": "/app",
        "env": {"DEBUG": "1"},
        "priority": 10,
        "timeout": 60
    },
    "id": 1
}

// Response
{
    "jsonrpc": "2.0",
    "result": {"task_id": "019505a1-..."},
    "id": 1
}
```

Task types and their required params:

| type | Required params | Optional params |
|------|----------------|-----------------|
| `run_command` | `command` | `working_directory`, `env` |
| `run_tests` | `runner` | `args`, `working_directory` |
| `run_analyzer` | `analyzer` | `args`, `working_directory` |
| `agent_task` | `action` | `parameters` |

Common optional params for all types: `priority` (int), `timeout` (seconds).

#### task.status

Get full task details.

```json
// Request
{"jsonrpc": "2.0", "method": "task.status", "params": {"task_id": "019505a1-..."}, "id": 2}

// Response
{
    "jsonrpc": "2.0",
    "result": {
        "id": "019505a1-...",
        "type": "run_command",
        "status": "completed",
        "priority": 0,
        "payload": {"command": "echo hello", "working_directory": null, "env": {}},
        "result": {"exit_code": 0, "stdout": "hello\n", "stderr": "", "duration": 0.012, "success": true},
        "error": null,
        "created_by": "user",
        "created_at": "2026-03-26 14:30:00.000000",
        "started_at": "2026-03-26 14:30:00.001000",
        "finished_at": "2026-03-26 14:30:00.013000",
        "scheduled_at": null,
        "retry_count": 0,
        "max_retries": 3,
        "timeout": 300
    },
    "id": 2
}
```

#### task.result

Lightweight version — just status + result/error.

```json
{"jsonrpc": "2.0", "method": "task.result", "params": {"task_id": "019505a1-..."}, "id": 3}
```

#### task.cancel

Cancel a pending or running task. Returns `false` if already terminal.

```json
{"jsonrpc": "2.0", "method": "task.cancel", "params": {"task_id": "019505a1-..."}, "id": 4}
// {"jsonrpc": "2.0", "result": {"success": true}, "id": 4}
```

#### task.list

List tasks with optional filtering and pagination.

```json
{
    "jsonrpc": "2.0",
    "method": "task.list",
    "params": {"status": "failed", "limit": 10, "offset": 0},
    "id": 5
}

// Response
{
    "jsonrpc": "2.0",
    "result": {
        "tasks": [{"id": "...", "type": "...", "status": "failed", ...}],
        "total": 3
    },
    "id": 5
}
```

Valid status values: `pending`, `scheduled`, `running`, `completed`, `failed`, `cancelled`.

#### task.logs

Get execution logs for a task.

```json
{"jsonrpc": "2.0", "method": "task.logs", "params": {"task_id": "019505a1-..."}, "id": 6}

// Response
{
    "jsonrpc": "2.0",
    "result": {
        "logs": [
            {"id": 1, "level": "info", "message": "Executing command: echo hello", "context": null, "created_at": "2026-03-26 14:30:00.001000"},
            {"id": 2, "level": "info", "message": "Command completed successfully", "context": null, "created_at": "2026-03-26 14:30:00.013000"}
        ]
    },
    "id": 6
}
```

#### schedule.create

Create a cron-based recurring schedule.

```json
{
    "jsonrpc": "2.0",
    "method": "schedule.create",
    "params": {
        "name": "nightly-tests",
        "cron": "0 2 * * *",
        "type": "run_tests",
        "params": {"runner": "vendor/bin/phpunit", "args": ["--testsuite", "Kernel"]}
    },
    "id": 7
}
// {"jsonrpc": "2.0", "result": {"schedule_id": "019505b2-..."}, "id": 7}
```

Cron format: `minute hour day month weekday` (standard 5-field).

Examples:
- `* * * * *` — every minute
- `*/5 * * * *` — every 5 minutes
- `0 2 * * *` — daily at 2:00 AM
- `0 9-17 * * 1-5` — hourly 9 AM–5 PM, Monday–Friday
- `0 0 1,15 * *` — 1st and 15th of month at midnight

#### schedule.list

```json
{"jsonrpc": "2.0", "method": "schedule.list", "params": {}, "id": 8}
```

#### schedule.toggle

Enable or disable a schedule.

```json
{"jsonrpc": "2.0", "method": "schedule.toggle", "params": {"schedule_id": "...", "enabled": false}, "id": 9}
```

#### schedule.delete

```json
{"jsonrpc": "2.0", "method": "schedule.delete", "params": {"schedule_id": "..."}, "id": 10}
```

### Batch Requests

Send multiple requests in a single call:

```json
[
    {"jsonrpc": "2.0", "method": "task.submit", "params": {"type": "run_command", "command": "echo 1"}, "id": 1},
    {"jsonrpc": "2.0", "method": "task.submit", "params": {"type": "run_command", "command": "echo 2"}, "id": 2},
    {"jsonrpc": "2.0", "method": "task.list", "params": {}, "id": 3}
]
```

Response is a JSON array with results in the same order.

### Error Codes

| Code | Meaning |
|------|---------|
| -32700 | Parse error — invalid JSON |
| -32600 | Invalid request — missing method |
| -32601 | Method not found |
| -32602 | Invalid params — missing required param |
| -32603 | Internal error — handler threw exception |
| -32000 | Task not found |

---

## Worker

The worker is a long-running process that:
1. Polls SQLite for scheduled tasks that became ready
2. Checks cron schedules every 60 seconds and dispatches due tasks
3. Handles graceful shutdown on SIGINT/SIGTERM

### Run directly

```bash
php libs/TaskBus/bin/worker.php
```

### Environment variables

| Variable | Default | Description |
|----------|---------|-------------|
| `TASK_BUS_DB_PATH` | `data/task-bus.sqlite` | SQLite database path |
| `TASK_BUS_MAX_CONCURRENT` | `4` | Max concurrent tasks |
| `TASK_BUS_POLL_INTERVAL` | `200000` | Poll interval in microseconds (200ms) |

### Run via Docker

```bash
cd libs/TaskBus
docker compose up -d
```

Services:
- `task-bus-server` — JSON-RPC on port 9800, healthcheck enabled
- `task-bus-worker` — Polling worker, depends on server health

Shared SQLite volume: `task-bus-data` mounted at `/app/data/`.

---

## Configuration

```php
use AppDevPanel\TaskBus\TaskBusConfig;
use AppDevPanel\TaskBus\TaskBusFactory;

$config = new TaskBusConfig(
    databasePath: '/var/data/task-bus.sqlite',  // SQLite file path
    defaultTimeout: 300,                         // 5 min default per task
    maxConcurrentTasks: 4,                       // Worker concurrency limit
    workerSleepInterval: 200_000,                // 200ms poll interval
    allowedCommands: [],                         // Empty = allow all
);

$bus = TaskBusFactory::create($config);
```

### Custom process runner

Replace the default `SymfonyProcessRunner` for testing or sandboxing:

```php
use AppDevPanel\TaskBus\Process\ProcessRunnerInterface;
use AppDevPanel\TaskBus\Process\ProcessResult;

$mockRunner = new class implements ProcessRunnerInterface {
    public function run(string $command, ?string $workingDirectory = null, array $env = [], ?int $timeout = null): ProcessResult {
        return new ProcessResult(exitCode: 0, stdout: 'mocked', stderr: '', duration: 0.0);
    }
};

$bus = TaskBusFactory::createInMemory(processRunner: $mockRunner);
```

### Custom agent actions

```php
$bus = TaskBusFactory::create(
    agentActions: [
        'fix_formatting' => 'vendor/bin/mago fmt',
        'run_lint'       => 'vendor/bin/mago lint',
        'run_tests'      => 'vendor/bin/phpunit',
        'generate_docs'  => 'php bin/generate-docs.php',
        'deploy_staging' => 'bash scripts/deploy.sh staging',
    ],
);
```

---

## Task Lifecycle

```
                ┌──────────┐
                │ Scheduled│ ← scheduleCommand()
                └────┬─────┘
                     │ (time reached + worker picks up)
                     ▼
┌────────┐     ┌─────────┐     ┌───────────┐
│ submit │────▶│ Pending │────▶│  Running   │
└────────┘     └─────────┘     └─────┬─────┘
                                     │
                         ┌───────────┼───────────┐
                         ▼           ▼           ▼
                   ┌───────────┐ ┌────────┐ ┌───────────┐
                   │ Completed │ │ Failed │ │ Cancelled │
                   └───────────┘ └────┬───┘ └───────────┘
                                      │
                                      ▼ (if canRetry)
                                 ┌─────────┐
                                 │ Pending  │ (retry)
                                 └─────────┘
```

- **Pending** — created, waiting for dispatch
- **Scheduled** — deferred, waiting for `scheduledAt` time
- **Running** — handler is executing the process
- **Completed** — process exited with code 0
- **Failed** — process exited non-zero or handler threw exception
- **Cancelled** — manually cancelled via `cancel()`

Retries: on failure, `TaskRetryMiddleware` increments `retry_count`. If `retry_count < max_retries`, task returns to Pending. Otherwise, stays Failed with `retries_exhausted: true`.

---

## Priorities

Tasks are dequeued in priority order (highest first), then by creation time (oldest first).

| Priority | Value | Use case |
|----------|------:|----------|
| Critical | 20 | Emergency fixes, blocking CI |
| High | 10 | Agent tasks, fast commands |
| Normal | 0 | Default — tests, analyzers |
| Low | -10 | Background cleanup, heavy batch jobs |

---

## Integration with MCP Server

The TaskBus is designed to be called from the ADP MCP server, allowing LLM agents to submit and monitor tasks:

```php
// In MCP tool handler
$taskId = $bus->submitAgentTask(
    action: 'run_tests',
    parameters: ['context' => 'Verifying fix for issue #42'],
);

// Poll for completion
$task = $bus->status($taskId);
if ($task->status === TaskStatus::Completed) {
    return "Tests passed: {$task->result['stdout']}";
}
```

---

## SQLite Direct Access

For advanced queries, access SQLite directly:

```php
use AppDevPanel\TaskBus\Storage\PdoFactory;

$pdo = PdoFactory::create('/path/to/task-bus.sqlite');

// Custom query
$stmt = $pdo->prepare('SELECT type, COUNT(*) as cnt FROM tasks GROUP BY type');
$stmt->execute();
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Cleanup old tasks
$pdo->exec("DELETE FROM tasks WHERE finished_at < datetime('now', '-7 days')");
```

Tables: `tasks`, `task_logs`, `schedules`. See `SqliteSchema::create()` for full DDL.

# TaskBus Module

Durable task queue and workflow engine for ADP. Runs commands, tests, analyzers, scheduled tasks,
and LLM agent jobs. Built on Symfony Messenger with SQLite storage and JSON-RPC transport.

## Dependencies

- `symfony/messenger` — Message bus, middleware chain, handler dispatch
- `symfony/process` — External process execution with timeouts
- `symfony/scheduler` — Cron-based recurring task scheduling
- `symfony/uid` — UUID v7 generation for task IDs
- `psr/log` — Logging interface
- `ext-pdo_sqlite` — SQLite storage

## Package

- Composer: `app-dev-panel/task-bus`
- Namespace: `AppDevPanel\TaskBus\`
- PHP: 8.4+

## Key Classes

| Class | Purpose |
|-------|---------|
| `TaskBus` | Facade: dispatch, cancel, status, list, logs |
| `TaskBusFactory` | Wires bus with all handlers, middleware, storage |
| `Task` | Entity: id, type, status, payload, result, timestamps |
| `TaskStatus` | Enum: pending, scheduled, running, completed, failed, cancelled |
| `TaskPriority` | Enum: low(-10), normal(0), high(10), critical(20) |
| `TaskBusConfig` | Config: database path, timeouts, concurrency limits |

## Messages & Handlers

| Message | Handler | Purpose |
|---------|---------|---------|
| `RunCommand` | `RunCommandHandler` | Execute shell command |
| `RunTests` | `RunTestsHandler` | Run test suite (PHPUnit, Vitest) |
| `RunAnalyzer` | `RunAnalyzerHandler` | Run static analyzer (Mago, PHPStan) |
| `AgentTask` | `AgentTaskHandler` | Execute LLM agent action by name |

## Directory Structure

```
src/
├── Task.php                    # Task entity
├── TaskBus.php                 # Main facade
├── TaskBusConfig.php           # Configuration
├── TaskBusFactory.php          # Standalone wiring
├── TaskStatus.php              # Status enum
├── TaskPriority.php            # Priority enum
├── TaskLog.php                 # Log entry value object
├── Message/                    # Message value objects
│   ├── AbstractTaskMessage.php
│   ├── RunCommand.php
│   ├── RunTests.php
│   ├── RunAnalyzer.php
│   └── AgentTask.php
├── Handler/                    # Message handlers
│   ├── RunCommandHandler.php
│   ├── RunTestsHandler.php
│   ├── RunAnalyzerHandler.php
│   └── AgentTaskHandler.php
├── Middleware/                  # Messenger middleware
│   ├── TaskPersistenceMiddleware.php   # Auto-persist tasks to SQLite
│   └── TaskRetryMiddleware.php         # Retry with back-off
├── Transport/                  # JSON-RPC transport
│   ├── JsonRpcServer.php       # TCP/Unix socket server
│   ├── JsonRpcHandler.php      # Method routing
│   ├── JsonRpcRequest.php
│   ├── JsonRpcResponse.php
│   └── JsonRpcError.php
├── Scheduler/                  # Cron scheduler
│   ├── CronExpression.php      # Cron parser (min/hour/day/month/weekday)
│   └── ScheduleRegistry.php    # SQLite-backed schedule CRUD
├── Storage/                    # Persistence
│   ├── TaskRepositoryInterface.php
│   ├── SqliteTaskRepository.php
│   ├── SqliteSchema.php        # DDL + indexes
│   └── PdoFactory.php          # PDO creation with WAL mode
└── Process/                    # External process execution
    ├── ProcessRunnerInterface.php
    ├── SymfonyProcessRunner.php
    └── ProcessResult.php
```

## JSON-RPC Methods

| Method | Params | Response |
|--------|--------|----------|
| `task.submit` | `{type, command\|runner\|analyzer, ...}` | `{task_id}` |
| `task.cancel` | `{task_id}` | `{success}` |
| `task.status` | `{task_id}` | `{task}` |
| `task.result` | `{task_id}` | `{result, error}` |
| `task.list` | `{status?, limit?, offset?}` | `{tasks, total}` |
| `task.logs` | `{task_id}` | `{logs}` |
| `schedule.create` | `{name, cron, type, params}` | `{schedule_id}` |
| `schedule.delete` | `{schedule_id}` | `{success}` |
| `schedule.list` | `{}` | `{schedules}` |
| `schedule.toggle` | `{schedule_id, enabled}` | `{success}` |

## SQLite Schema

Three tables: `tasks` (task state + payload + result), `task_logs` (per-task log entries),
`schedules` (cron-based recurring task definitions). WAL mode enabled for concurrent read access.

## HTTP Endpoint (primary)

JSON-RPC 2.0 is exposed via the API inspector at `POST /inspect/api/taskbus` — no separate port.
Health check at `GET /inspect/api/taskbus/status`. See `libs/API/src/Inspector/Controller/TaskBusController.php`.

## Standalone TCP Server (optional)

`JsonRpcServer` can run as a standalone TCP server on a dedicated port via `taskbus:serve`.
Only needed when running TaskBus outside the API server context.

## Worker

Background worker polls SQLite for scheduled/deferred tasks: `taskbus:worker`.
Required for `scheduleCommand()` and cron-based schedules. Not needed for synchronous dispatch.
Started automatically with `make serve`.

## Docker

`docker-compose.yml` provides two services:
- `task-bus-server` — Standalone JSON-RPC server on port 9800
- `task-bus-worker` — Polling worker for scheduled/deferred tasks

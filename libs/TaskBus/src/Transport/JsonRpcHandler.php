<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Transport;

use AppDevPanel\TaskBus\Scheduler\ScheduleRegistry;
use AppDevPanel\TaskBus\Task;
use AppDevPanel\TaskBus\TaskBus;
use AppDevPanel\TaskBus\TaskPriority;
use AppDevPanel\TaskBus\TaskStatus;
use DateTimeImmutable;

final readonly class JsonRpcHandler
{
    public function __construct(
        private TaskBus $taskBus,
        private ?ScheduleRegistry $scheduleRegistry = null,
    ) {}

    public function handle(string $json): ?string
    {
        try {
            $request = JsonRpcRequest::fromJson($json);
        } catch (\JsonException $e) {
            return JsonRpcResponse::error(null, JsonRpcError::parseError($e->getMessage()))->toJson();
        }

        if (is_array($request)) {
            if ($request === []) {
                return JsonRpcResponse::error(null, JsonRpcError::invalidRequest())->toJson();
            }
            $responses = array_filter(
                array_map($this->handleSingle(...), $request),
                static fn(?JsonRpcResponse $r): bool => $r !== null,
            );
            if ($responses === []) {
                return null;
            }
            return json_encode(array_map(
                static fn(JsonRpcResponse $r): array => $r->toArray(),
                array_values($responses),
            ), JSON_THROW_ON_ERROR);
        }

        $response = $this->handleSingle($request);
        return $response?->toJson();
    }

    private function handleSingle(JsonRpcRequest $request): ?JsonRpcResponse
    {
        if ($request->method === '') {
            return $request->isNotification()
                ? null
                : JsonRpcResponse::error($request->id, JsonRpcError::invalidRequest());
        }

        try {
            $result = match ($request->method) {
                'task.submit' => $this->taskSubmit($request->params),
                'task.cancel' => $this->taskCancel($request->params),
                'task.status' => $this->taskStatus($request->params),
                'task.result' => $this->taskResult($request->params),
                'task.list' => $this->taskList($request->params),
                'task.logs' => $this->taskLogs($request->params),
                'schedule.create' => $this->scheduleCreate($request->params),
                'schedule.delete' => $this->scheduleDelete($request->params),
                'schedule.list' => $this->scheduleList(),
                'schedule.toggle' => $this->scheduleToggle($request->params),
                default => throw new \InvalidArgumentException("Method not found: {$request->method}"),
            };
        } catch (\InvalidArgumentException $e) {
            return $request->isNotification()
                ? null
                : JsonRpcResponse::error($request->id, JsonRpcError::methodNotFound($request->method));
        } catch (\Throwable $e) {
            return $request->isNotification()
                ? null
                : JsonRpcResponse::error($request->id, JsonRpcError::internalError($e->getMessage()));
        }

        return $request->isNotification() ? null : JsonRpcResponse::success($request->id, $result);
    }

    private function taskSubmit(array $params): array
    {
        $type = $params['type'] ?? throw new \InvalidArgumentException('Missing required param: type');
        $priority = isset($params['priority']) ? TaskPriority::from((int) $params['priority']) : TaskPriority::Normal;
        $timeout = isset($params['timeout']) ? (int) $params['timeout'] : null;

        $taskId = match ($type) {
            'run_command' => $this->taskBus->runCommand(
                command: $params['command'] ?? throw new \InvalidArgumentException('Missing: command'),
                workingDirectory: $params['working_directory'] ?? null,
                env: $params['env'] ?? [],
                priority: $priority,
                timeout: $timeout,
            ),
            'run_tests' => $this->taskBus->runTests(
                runner: $params['runner'] ?? throw new \InvalidArgumentException('Missing: runner'),
                args: $params['args'] ?? [],
                workingDirectory: $params['working_directory'] ?? null,
                priority: $priority,
                timeout: $timeout,
            ),
            'run_analyzer' => $this->taskBus->runAnalyzer(
                analyzer: $params['analyzer'] ?? throw new \InvalidArgumentException('Missing: analyzer'),
                args: $params['args'] ?? [],
                workingDirectory: $params['working_directory'] ?? null,
                priority: $priority,
                timeout: $timeout,
            ),
            'agent_task' => $this->taskBus->submitAgentTask(
                action: $params['action'] ?? throw new \InvalidArgumentException('Missing: action'),
                parameters: $params['parameters'] ?? [],
                priority: $priority,
                timeout: $timeout,
            ),
            default => throw new \InvalidArgumentException("Unknown task type: {$type}"),
        };

        return ['task_id' => $taskId];
    }

    private function taskCancel(array $params): array
    {
        $taskId = $params['task_id'] ?? throw new \InvalidArgumentException('Missing: task_id');

        return ['success' => $this->taskBus->cancel($taskId)];
    }

    private function taskStatus(array $params): array
    {
        $taskId = $params['task_id'] ?? throw new \InvalidArgumentException('Missing: task_id');
        $task = $this->taskBus->status($taskId);

        if ($task === null) {
            throw new \RuntimeException("Task not found: {$taskId}");
        }

        return $this->serializeTask($task);
    }

    private function taskResult(array $params): array
    {
        $taskId = $params['task_id'] ?? throw new \InvalidArgumentException('Missing: task_id');
        $task = $this->taskBus->status($taskId);

        if ($task === null) {
            throw new \RuntimeException("Task not found: {$taskId}");
        }

        return [
            'task_id' => $task->id,
            'status' => $task->status->value,
            'result' => $task->result,
            'error' => $task->error,
        ];
    }

    private function taskList(array $params): array
    {
        $status = isset($params['status']) ? TaskStatus::from($params['status']) : null;
        $limit = (int) ($params['limit'] ?? 50);
        $offset = (int) ($params['offset'] ?? 0);

        $tasks = $this->taskBus->list($status, $limit, $offset);

        return [
            'tasks' => array_map($this->serializeTask(...), $tasks),
            'total' => $this->taskBus->count($status),
        ];
    }

    private function taskLogs(array $params): array
    {
        $taskId = $params['task_id'] ?? throw new \InvalidArgumentException('Missing: task_id');

        $logs = $this->taskBus->logs($taskId);

        return [
            'logs' => array_map(static fn($log): array => [
                'id' => $log->id,
                'level' => $log->level,
                'message' => $log->message,
                'context' => $log->context,
                'created_at' => $log->createdAt->format('Y-m-d H:i:s.u'),
            ], $logs),
        ];
    }

    private function scheduleCreate(array $params): array
    {
        $registry = $this->requireScheduleRegistry();

        $id = $registry->create(
            name: $params['name'] ?? throw new \InvalidArgumentException('Missing: name'),
            cron: $params['cron'] ?? throw new \InvalidArgumentException('Missing: cron'),
            messageType: $params['type'] ?? throw new \InvalidArgumentException('Missing: type'),
            messagePayload: $params['params'] ?? [],
        );

        return ['schedule_id' => $id];
    }

    private function scheduleDelete(array $params): array
    {
        $registry = $this->requireScheduleRegistry();
        $scheduleId = $params['schedule_id'] ?? throw new \InvalidArgumentException('Missing: schedule_id');

        return ['success' => $registry->delete($scheduleId)];
    }

    private function scheduleList(): array
    {
        $registry = $this->requireScheduleRegistry();

        return ['schedules' => $registry->list()];
    }

    private function scheduleToggle(array $params): array
    {
        $registry = $this->requireScheduleRegistry();
        $scheduleId = $params['schedule_id'] ?? throw new \InvalidArgumentException('Missing: schedule_id');
        $enabled = (bool) ($params['enabled'] ?? true);

        return ['success' => $registry->toggle($scheduleId, $enabled)];
    }

    private function requireScheduleRegistry(): ScheduleRegistry
    {
        return $this->scheduleRegistry ?? throw new \RuntimeException('Scheduler not configured');
    }

    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->id,
            'type' => $task->type,
            'status' => $task->status->value,
            'priority' => $task->priority->value,
            'payload' => $task->payload,
            'result' => $task->result,
            'error' => $task->error,
            'created_by' => $task->createdBy,
            'created_at' => $task->createdAt->format('Y-m-d H:i:s.u'),
            'started_at' => $task->startedAt?->format('Y-m-d H:i:s.u'),
            'finished_at' => $task->finishedAt?->format('Y-m-d H:i:s.u'),
            'scheduled_at' => $task->scheduledAt?->format('Y-m-d H:i:s.u'),
            'retry_count' => $task->retryCount,
            'max_retries' => $task->maxRetries,
            'timeout' => $task->timeoutSeconds,
        ];
    }
}

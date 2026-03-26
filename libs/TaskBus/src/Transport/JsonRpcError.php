<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Transport;

final readonly class JsonRpcError
{
    public const int PARSE_ERROR = -32700;
    public const int INVALID_REQUEST = -32600;
    public const int METHOD_NOT_FOUND = -32601;
    public const int INVALID_PARAMS = -32602;
    public const int INTERNAL_ERROR = -32603;
    public const int TASK_NOT_FOUND = -32000;

    public function __construct(
        public int $code,
        public string $message,
        public mixed $data = null,
    ) {}

    public static function parseError(string $message = 'Parse error'): self
    {
        return new self(self::PARSE_ERROR, $message);
    }

    public static function invalidRequest(string $message = 'Invalid request'): self
    {
        return new self(self::INVALID_REQUEST, $message);
    }

    public static function methodNotFound(string $method): self
    {
        return new self(self::METHOD_NOT_FOUND, "Method not found: {$method}");
    }

    public static function invalidParams(string $message): self
    {
        return new self(self::INVALID_PARAMS, $message);
    }

    public static function internalError(string $message = 'Internal error'): self
    {
        return new self(self::INTERNAL_ERROR, $message);
    }

    public static function taskNotFound(string $taskId): self
    {
        return new self(self::TASK_NOT_FOUND, "Task not found: {$taskId}");
    }

    public function toArray(): array
    {
        $result = ['code' => $this->code, 'message' => $this->message];
        if ($this->data !== null) {
            $result['data'] = $this->data;
        }
        return $result;
    }
}

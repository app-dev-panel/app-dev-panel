<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Transport;

final readonly class JsonRpcResponse
{
    private function __construct(
        public string|int|null $id,
        public mixed $result = null,
        public ?JsonRpcError $error = null,
    ) {}

    public static function success(string|int|null $id, mixed $result): self
    {
        return new self(id: $id, result: $result);
    }

    public static function error(string|int|null $id, JsonRpcError $error): self
    {
        return new self(id: $id, error: $error);
    }

    public function toArray(): array
    {
        $response = ['jsonrpc' => '2.0', 'id' => $this->id];

        if ($this->error !== null) {
            $response['error'] = $this->error->toArray();
        } else {
            $response['result'] = $this->result;
        }

        return $response;
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }
}

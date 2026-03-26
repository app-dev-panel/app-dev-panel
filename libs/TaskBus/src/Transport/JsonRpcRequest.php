<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Transport;

final readonly class JsonRpcRequest
{
    public function __construct(
        public string $method,
        public array $params = [],
        public string|int|null $id = null,
    ) {}

    public function isNotification(): bool
    {
        return $this->id === null;
    }

    /**
     * @return self|list<self>
     */
    public static function fromJson(string $json): self|array
    {
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        if (array_is_list($data)) {
            return array_map(self::fromArray(...), $data);
        }

        return self::fromArray($data);
    }

    private static function fromArray(array $data): self
    {
        return new self(method: $data['method'] ?? '', params: $data['params'] ?? [], id: $data['id'] ?? null);
    }
}

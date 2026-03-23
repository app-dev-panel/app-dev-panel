<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final readonly class CacheOperationRecord
{
    public function __construct(
        public string $pool,
        public string $operation,
        public string $key,
        public bool $hit = false,
        public float $duration = 0.0,
        public mixed $value = null,
    ) {}

    public function toArray(): array
    {
        return [
            'pool' => $this->pool,
            'operation' => $this->operation,
            'key' => $this->key,
            'hit' => $this->hit,
            'duration' => $this->duration,
            'value' => $this->value,
        ];
    }
}

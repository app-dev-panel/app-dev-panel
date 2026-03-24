<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final readonly class RedisCommandRecord
{
    public function __construct(
        public string $connection,
        public string $command,
        public array $arguments,
        public mixed $result,
        public float $duration,
        public ?string $error = null,
        public string $line = '',
    ) {}

    public function toArray(): array
    {
        return [
            'connection' => $this->connection,
            'command' => $this->command,
            'arguments' => $this->arguments,
            'result' => $this->result,
            'duration' => $this->duration,
            'error' => $this->error,
            'line' => $this->line,
        ];
    }
}

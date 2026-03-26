<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Process;

final readonly class ProcessResult
{
    public function __construct(
        public int $exitCode,
        public string $stdout,
        public string $stderr,
        public float $duration,
    ) {}

    public function isSuccessful(): bool
    {
        return $this->exitCode === 0;
    }

    public function toArray(): array
    {
        return [
            'exit_code' => $this->exitCode,
            'stdout' => $this->stdout,
            'stderr' => $this->stderr,
            'duration' => round($this->duration, 3),
            'success' => $this->isSuccessful(),
        ];
    }
}

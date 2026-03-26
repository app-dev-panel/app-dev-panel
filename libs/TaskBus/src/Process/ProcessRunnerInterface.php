<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Process;

interface ProcessRunnerInterface
{
    /**
     * @param non-empty-string $command Shell command to execute
     * @param non-empty-string|null $workingDirectory
     * @param array<string, string> $env Additional environment variables
     * @param positive-int|null $timeout Timeout in seconds
     */
    public function run(
        string $command,
        ?string $workingDirectory = null,
        array $env = [],
        ?int $timeout = null,
    ): ProcessResult;
}

<?php

declare(strict_types=1);

namespace AppDevPanel\TaskBus\Process;

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

final class SymfonyProcessRunner implements ProcessRunnerInterface
{
    public function run(
        string $command,
        ?string $workingDirectory = null,
        array $env = [],
        ?int $timeout = null,
    ): ProcessResult {
        $process = Process::fromShellCommandLine($command, $workingDirectory, $env !== [] ? $env : null);

        if ($timeout !== null) {
            $process->setTimeout($timeout);
        }

        $start = microtime(true);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return new ProcessResult(
                exitCode: 124,
                stdout: $process->getOutput(),
                stderr: $process->getErrorOutput() . "\n[TIMEOUT] Process exceeded {$timeout}s limit",
                duration: microtime(true) - $start,
            );
        }

        return new ProcessResult(
            exitCode: $process->getExitCode() ?? 1,
            stdout: $process->getOutput(),
            stderr: $process->getErrorOutput(),
            duration: microtime(true) - $start,
        );
    }
}

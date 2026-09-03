<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandResponse;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Shared subprocess plumbing for inspector commands: every process gets the
 * {@see CommandTimeout} ceiling (optionally shortened per instance) and a timeout
 * is reported as a failed {@see CommandResponse} instead of an uncaught exception.
 */
trait ProcessCommandTrait
{
    private int $timeout = CommandTimeout::DEFAULT;

    /**
     * Returns a copy limited to `$seconds` (clamped to `1..CommandTimeout::DEFAULT`).
     */
    public function withTimeout(int $seconds): static
    {
        $clone = clone $this;
        $clone->timeout = CommandTimeout::clamp($seconds);

        return $clone;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Runs `$process` in `$workingDirectory` under the configured timeout.
     *
     * @return bool false when the process was killed because it exceeded the timeout
     */
    private function runProcess(Process $process, string $workingDirectory): bool
    {
        $process->setWorkingDirectory($workingDirectory)->setTimeout($this->timeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return false;
        }

        return true;
    }

    private function timedOutResponse(): CommandResponse
    {
        return new CommandResponse(status: CommandResponse::STATUS_FAIL, result: null, errors: [sprintf(
            'Command timed out after %d seconds.',
            $this->timeout,
        )]);
    }
}

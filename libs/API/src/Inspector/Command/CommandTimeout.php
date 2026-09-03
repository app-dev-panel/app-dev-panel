<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Inspector\Command;

use AppDevPanel\Api\Inspector\CommandInterface;

/**
 * Hard wall-clock limit for inspector subprocesses (tests, linters, shell commands).
 */
final class CommandTimeout
{
    /**
     * Seconds. Matches the "single playground fixture / scenario run" ceiling in the
     * root CLAUDE.md timeout table — never raise it. Per-request overrides may only
     * shorten it (see {@see self::clamp()}).
     */
    public const int DEFAULT = 120;

    public static function clamp(int $seconds): int
    {
        return max(1, min($seconds, self::DEFAULT));
    }

    /**
     * Applies an optional per-request `timeout` query parameter (seconds). It can only
     * shorten the ceiling; commands without {@see ProcessCommandTrait} are returned as-is.
     *
     * @param array<string, mixed> $queryParams
     */
    public static function applyFromQuery(CommandInterface $command, array $queryParams): CommandInterface
    {
        $requested = $queryParams['timeout'] ?? null;
        if (!is_numeric($requested) || !method_exists($command, 'withTimeout')) {
            return $command;
        }

        /** @var CommandInterface */
        return $command->withTimeout((int) $requested);
    }
}

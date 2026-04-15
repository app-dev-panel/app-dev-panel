<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Allowlist for ACP agent commands. The ACP connect endpoint accepts a
 * command from the client and spawns it as a subprocess. Even though
 * `escapeshellarg` blocks shell injection, without this allowlist any
 * binary on PATH could be launched as the web-server user.
 *
 * The check is intentionally loose — it compares the command `basename`
 * so both `npx` and `/usr/local/bin/npx` are accepted while
 * `/bin/sh`, `curl`, etc. are rejected.
 */
final class AcpCommandAllowlist
{
    /**
     * @var list<string>
     */
    public const DEFAULT_COMMANDS = ['npx', 'claude', 'gemini', 'node'];

    /**
     * @var list<string>
     */
    private readonly array $commands;

    /**
     * @param list<string>|null $commands Pass `null` for {@see DEFAULT_COMMANDS}.
     */
    public function __construct(?array $commands = null)
    {
        $this->commands = $commands ?? self::DEFAULT_COMMANDS;
    }

    public function isAllowed(string $command): bool
    {
        if ($command === '') {
            return false;
        }

        $basename = basename($command);

        return in_array($basename, $this->commands, true);
    }

    /**
     * @return list<string>
     */
    public function getCommands(): array
    {
        return $this->commands;
    }
}

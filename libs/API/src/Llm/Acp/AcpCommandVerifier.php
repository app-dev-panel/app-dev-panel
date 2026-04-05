<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Default command verifier: uses `which`/`where` to check if a command exists on PATH.
 */
final class AcpCommandVerifier implements AcpCommandVerifierInterface
{
    public function isAvailable(string $command): bool
    {
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        exec(sprintf('%s %s 2>/dev/null', $which, escapeshellarg($command)), $output, $exitCode);

        return $exitCode === 0;
    }
}

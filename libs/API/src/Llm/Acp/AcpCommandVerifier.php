<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use Symfony\Component\Process\Process;

/**
 * Default command verifier: uses `which`/`where` to check if a command exists on PATH.
 */
final class AcpCommandVerifier implements AcpCommandVerifierInterface
{
    private const float LOOKUP_TIMEOUT = 5.0;

    public function isAvailable(string $command): bool
    {
        $which = PHP_OS_FAMILY === 'Windows' ? 'where' : 'which';
        $process = new Process([$which, $command]);
        $process->setTimeout(self::LOOKUP_TIMEOUT);

        try {
            $process->run();
        } catch (\Throwable) {
            return false;
        }

        return $process->isSuccessful();
    }
}

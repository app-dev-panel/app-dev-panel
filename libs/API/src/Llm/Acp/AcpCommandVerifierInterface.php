<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

/**
 * Checks whether an ACP agent command is available on the system.
 */
interface AcpCommandVerifierInterface
{
    public function isAvailable(string $command): bool;
}

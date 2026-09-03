<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

/**
 * Sends a message to every connected debug server.
 *
 * Implementations must never block the caller for longer than a short, bounded
 * timeout and must never emit PHP warnings or notices — broadcasting is a
 * best-effort side channel that must not affect the application.
 */
interface BroadcasterInterface
{
    /**
     * @param int $type One of the `Connection::MESSAGE_TYPE_*` constants.
     *
     * @return array Unique errors encountered during broadcast (empty on success).
     */
    public function broadcast(int $type, string $data): array;
}

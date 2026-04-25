<?php

/** @noinspection PhpComposerExtensionStubsInspection */

declare(strict_types=1);

namespace AppDevPanel\Api\Debug;

use AppDevPanel\Kernel\DebugServer\Connection;

/**
 * Creates SSE stream callbacks backed by a UDP debug server socket.
 *
 * Instead of polling storage, the SSE endpoint listens for UDP broadcasts
 * from the app process (logs, var_dumps, entry-created notifications).
 * Each SSE HTTP worker gets its own socket — Broadcaster delivers to all.
 *
 * Falls back to no-op (heartbeat only) when the sockets extension is unavailable.
 */
final class LiveEventStreamFactory
{
    private const SSE_EVENT_MAP = [
        Connection::MESSAGE_TYPE_LOGGER => 'live-log',
        Connection::MESSAGE_TYPE_VAR_DUMPER => 'live-dump',
        Connection::MESSAGE_TYPE_ENTRY_CREATED => 'entry-created',
    ];

    /**
     * Creates an SSE stream callback that receives live events via UDP.
     *
     * @return array{0: \Closure, 1: \Closure} [streamCallback, closeCallback]
     */
    public static function create(int $deadlineSeconds = 30): array
    {
        if (!extension_loaded('sockets')) {
            return self::createFallback($deadlineSeconds);
        }

        try {
            $connection = Connection::create();
            $connection->bind();
        } catch (\RuntimeException) {
            return self::createFallback($deadlineSeconds);
        }

        $socket = $connection->getSocket();

        // Non-blocking recv: 50ms timeout so SSE can check for connection abort
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 0, 'usec' => 50_000]);
        socket_set_option($socket, SOL_SOCKET, SO_RCVBUF, 65536);

        // On Windows, SO_RCVTIMEO may not work reliably for UDP sockets.
        // Non-blocking mode prevents socket_recv from blocking forever.
        if (Connection::isWindows()) {
            socket_set_nonblock($socket);
        }

        $deadline = time() + $deadlineSeconds;

        $stream = static function (array &$buffer) use ($socket, &$deadline): bool {
            if (time() > $deadline) {
                return false;
            }

            $bytes = @socket_recv($socket, $datagram, 65536, 0);

            if ($bytes !== false && $bytes > 8 && $datagram !== null) {
                $event = self::parseDatagram($datagram);
                if ($event !== null) {
                    $buffer[] = json_encode($event, JSON_THROW_ON_ERROR);
                }
            }

            return true;
        };

        $close = static function () use ($connection): void {
            $connection->close();
        };

        return [$stream, $close];
    }

    /**
     * Parse a raw datagram into an SSE event payload.
     *
     * @return array{type: string, payload: mixed}|null
     */
    private static function parseDatagram(string $datagram): ?array
    {
        $length = unpack('P', substr($datagram, 0, 8));
        if ($length === false) {
            return null;
        }

        $encoded = substr($datagram, 8);
        $decoded = base64_decode($encoded, true);
        if ($decoded === false) {
            return null;
        }

        try {
            $message = json_decode($decoded, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!is_array($message) || count($message) < 2) {
            return null;
        }

        $messageType = $message[0];
        $payload = $message[1];
        $sseType = self::SSE_EVENT_MAP[$messageType] ?? 'unknown';

        // For entry-created, also emit debug-updated for backwards compatibility
        if ($messageType === Connection::MESSAGE_TYPE_ENTRY_CREATED) {
            return [
                'type' => $sseType,
                'payload' => is_string($payload) ? ['id' => $payload] : $payload,
            ];
        }

        return [
            'type' => $sseType,
            'payload' => $payload,
        ];
    }

    /**
     * Fallback when sockets extension is not available.
     * Returns a heartbeat-only stream (no live events).
     *
     * @return array{0: \Closure, 1: \Closure}
     */
    private static function createFallback(int $deadlineSeconds): array
    {
        $deadline = time() + $deadlineSeconds;

        $stream = static function (array &$buffer) use (&$deadline): bool {
            return time() <= $deadline;
        };

        $close = static function (): void {};

        return [$stream, $close];
    }
}

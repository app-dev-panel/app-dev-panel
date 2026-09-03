<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Helper;

/**
 * Runs a short-lived subprocess with a hard deadline and returns its stdout.
 *
 * Both stdout and stderr are drained concurrently via `stream_select()` so a
 * chatty child can never fill a pipe and deadlock the parent. When the
 * deadline passes the child is killed (SIGKILL) and `null` is returned.
 * Non-zero exit codes and spawn failures also yield `null`. PHP warnings
 * raised by the process functions are suppressed via a scoped error handler.
 */
final class BoundedProcess
{
    private const int CHUNK_SIZE = 8192;

    /**
     * @param float $timeout Seconds; clamped to at least 10 ms.
     *
     * @return string|null Trimmed stdout, or `null` on failure, non-zero exit, or timeout.
     */
    public static function run(string $command, ?string $cwd, float $timeout): ?string
    {
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $pipes = [];
        $process = self::silenced(static function () use ($command, $descriptors, &$pipes, $cwd) {
            return proc_open($command, $descriptors, $pipes, $cwd);
        });

        if (!is_resource($process) || count($pipes) !== 3) {
            return null;
        }

        /** @var array{0: resource, 1: resource, 2: resource} $pipes proc_open fills every 'pipe' descriptor on success */
        fclose($pipes[0]);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $output = self::drain($pipes[1], $pipes[2], microtime(true) + max(0.01, $timeout));

        fclose($pipes[1]);
        fclose($pipes[2]);

        if ($output === null) {
            self::silenced(static fn() => proc_terminate($process, 9));
            proc_close($process);
            return null;
        }

        return proc_close($process) === 0 ? trim($output) : null;
    }

    /**
     * Reads both pipes until EOF or the deadline.
     *
     * @param resource $stdout
     * @param resource $stderr
     *
     * @return string|null Captured stdout, or `null` when the deadline passed first.
     */
    private static function drain($stdout, $stderr, float $deadline): ?string
    {
        $output = '';
        $open = [$stdout, $stderr];

        while ($open !== []) {
            $remaining = $deadline - microtime(true);
            if ($remaining <= 0) {
                return null;
            }

            $read = $open;
            $write = null;
            $except = null;
            $seconds = (int) floor($remaining);
            $micro = (int) (($remaining - $seconds) * 1_000_000);
            $ready = self::silenced(static fn() => stream_select($read, $write, $except, $seconds, $micro));
            if ($ready === false) {
                return null;
            }

            foreach ($open as $index => $pipe) {
                $chunk = (string) fread($pipe, self::CHUNK_SIZE);
                if ($chunk === '' && feof($pipe)) {
                    unset($open[$index]);
                    continue;
                }
                if ($pipe === $stdout) {
                    $output .= $chunk;
                }
            }
        }

        return $output;
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    private static function silenced(callable $callback): mixed
    {
        set_error_handler(static fn(): bool => true);
        try {
            return $callback();
        } finally {
            restore_error_handler();
        }
    }
}

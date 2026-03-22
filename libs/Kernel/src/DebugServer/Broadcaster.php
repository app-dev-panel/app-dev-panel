<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\DebugServer;

use Throwable;

final class Broadcaster
{
    private const SOCKET_ECONNREFUSED = 61;

    /**
     * Broadcasts a message to all connected debug servers.
     *
     * @return array Unique errors encountered during broadcast.
     */
    public function broadcast(int $type, string $data): array
    {
        $files = glob(sys_get_temp_dir() . '/yii-dev-server-*.sock', GLOB_NOSORT);
        $uniqueErrors = [];
        $payload = json_encode([$type, $data], JSON_THROW_ON_ERROR);
        foreach ($files as $file) {
            $socket = @fsockopen('udg://' . $file, -1, $errno, $errstr);
            if ($errno === self::SOCKET_ECONNREFUSED) {
                if (file_exists($file)) {
                    unlink($file);
                }
                continue;
            }
            if ($errno !== 0) {
                $uniqueErrors[$errno] = $errstr;
                continue;
            }
            try {
                if (!$this->fwriteStream($socket, $payload)) {
                    $uniqueErrors[] = error_get_last();
                    /**
                     * Connection is closed.
                     */
                    continue;
                }
            } catch (Throwable $e) {
                throw $e;
            } finally {
                fclose($socket);
            }
        }
        return $uniqueErrors;
    }

    /**
     * @param resource $fp
     */
    private function fwriteStream($fp, string $data): int|false
    {
        $data = base64_encode($data);
        $strlen = strlen($data);
        fwrite($fp, pack('P', $strlen));
        for ($written = 0; $written < $strlen; $written += $fwrite) {
            $fwrite = fwrite($fp, substr($data, $written), Connection::DEFAULT_BUFFER_SIZE);
            usleep(Connection::DEFAULT_TIMEOUT * 5);
            if ($fwrite === false) {
                return $written;
            }
        }
        return $written;
    }
}

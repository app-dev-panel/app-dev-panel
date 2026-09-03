<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm\Acp;

use RuntimeException;

/**
 * Decides where the ACP daemon's Unix socket lives and verifies it can be trusted.
 *
 * The socket sits in `<storagePath>/.acp/daemon.sock`, a `0700` directory owned by the
 * current user. Only when that path would exceed the `sun_path` limit does it fall back
 * to a per-user `0700` directory under the system temp dir. Directory and socket node
 * are re-checked (owner, mode, type, no symlinks) before every connection so nobody can
 * plant a socket the panel would then talk to.
 */
final class AcpSocketLocator
{
    private const string SOCKET_DIRECTORY = '.acp';
    private const string SOCKET_FILENAME = 'daemon.sock';
    /** `sun_path` is 104 (macOS) / 108 (Linux) bytes including NUL; keep a safety margin. */
    private const int MAX_SOCKET_PATH_LENGTH = 100;

    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function getSocketPath(): string
    {
        return $this->getSocketDirectory() . '/' . self::SOCKET_FILENAME;
    }

    public function getSocketDirectory(): string
    {
        $preferred = rtrim($this->storagePath, '/\\') . '/' . self::SOCKET_DIRECTORY;
        if (strlen($preferred . '/' . self::SOCKET_FILENAME) <= self::MAX_SOCKET_PATH_LENGTH) {
            return $preferred;
        }

        $hash = substr(md5($this->storagePath), 0, 12);

        return sys_get_temp_dir() . '/adp-acp-' . $this->currentUid() . '/' . $hash;
    }

    /**
     * Creates the socket directory as `0700` (tightening it if it already exists) and
     * verifies it is a real directory owned by the current user.
     *
     * @throws RuntimeException when the directory cannot be trusted
     */
    public function ensureSocketDirectory(): string
    {
        $dir = $this->getSocketDirectory();

        // Never chmod through a symlink: that would change the mode of whatever it points at.
        if (is_link($dir)) {
            throw new RuntimeException(sprintf('ACP socket directory "%s" must not be a symlink.', $dir));
        }

        if (!is_dir($dir)) {
            $this->createDirectory($dir);
        }

        chmod($dir, 0o700);
        clearstatcache(true, $dir);

        $this->assertTrustedDirectory($dir);

        return $dir;
    }

    /**
     * @throws RuntimeException when the socket node or its directory cannot be trusted
     */
    public function assertTrustedSocket(): string
    {
        $socketPath = $this->getSocketPath();
        clearstatcache(true, $socketPath);

        $this->assertTrustedDirectory(dirname($socketPath));

        // filetype() uses lstat(): a symlink reports "link", never "socket".
        if (filetype($socketPath) !== 'socket') {
            throw new RuntimeException(sprintf('ACP daemon socket "%s" is not a Unix socket.', $socketPath));
        }

        if (fileowner($socketPath) !== $this->currentUid()) {
            throw new RuntimeException(sprintf(
                'ACP daemon socket "%s" is not owned by the current user.',
                $socketPath,
            ));
        }

        return $socketPath;
    }

    private function createDirectory(string $dir): void
    {
        if (file_exists($dir)) {
            throw new RuntimeException(sprintf('ACP socket path "%s" exists but is not a directory.', $dir));
        }

        if (!mkdir($dir, 0o700, true)) {
            throw new RuntimeException(sprintf('Cannot create ACP socket directory "%s".', $dir));
        }
    }

    private function assertTrustedDirectory(string $dir): void
    {
        if (is_link($dir) || !is_dir($dir)) {
            throw new RuntimeException(sprintf('ACP socket directory "%s" is missing or is a symlink.', $dir));
        }

        if (fileowner($dir) !== $this->currentUid()) {
            throw new RuntimeException(sprintf('ACP socket directory "%s" is not owned by the current user.', $dir));
        }

        // Existence was verified above, so fileperms() cannot return false here.
        if (((int) fileperms($dir) & 0o077) !== 0) {
            throw new RuntimeException(sprintf('ACP socket directory "%s" must be mode 0700.', $dir));
        }
    }

    private function currentUid(): int
    {
        if (function_exists('posix_geteuid')) {
            return posix_geteuid();
        }

        return (int) getmyuid();
    }
}

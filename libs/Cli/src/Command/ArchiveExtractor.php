<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use PharData;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

/**
 * Extracts release archives (`.zip`, `.tar.gz`) with a zip-slip guard: every entry
 * name must be relative and must not contain `..` segments.
 */
final class ArchiveExtractor
{
    /**
     * @throws RuntimeException when the archive is unreadable or an entry escapes `$path`
     */
    public function extractZip(string $archive, string $path): void
    {
        $zip = new ZipArchive();
        $result = $zip->open($archive);
        if ($result !== true) {
            throw new RuntimeException(sprintf('Failed to open zip archive (error code: %d)', $result));
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                self::assertSafeEntry((string) $zip->getNameIndex($i));
            }
            $zip->extractTo($path);
        } finally {
            $zip->close();
        }
    }

    /**
     * @throws RuntimeException when an entry escapes `$path`
     */
    public function extractTarGz(string $archive, string $path): void
    {
        // PharData::decompress() writes a sibling `.tar` (the `.gz` suffix stripped);
        // extract that and clean it up afterwards.
        $phar = new PharData($archive);
        $tarPath = null;
        try {
            $tarPhar = $phar->decompress() ?? throw new RuntimeException('Failed to decompress tar.gz archive.');
            $tarPath = $tarPhar->getPath();
            $this->assertSafeTar($tarPhar);
            $tarPhar->extractTo($path, null, true);
        } finally {
            // Force PharData destructors to release the files before unlink on Windows.
            unset($phar, $tarPhar);
            if ($tarPath !== null && file_exists($tarPath)) {
                unlink($tarPath);
            }
        }
    }

    private function assertSafeTar(PharData $tar): void
    {
        $prefix = 'phar://' . $tar->getPath() . '/';

        foreach (new RecursiveIteratorIterator($tar) as $entry) {
            $pathname = (string) $entry->getPathname();
            self::assertSafeEntry(self::stripPrefix($prefix, $pathname));
        }
    }

    /**
     * @throws RuntimeException
     */
    public static function assertSafeEntry(string $name): void
    {
        $normalized = str_replace('\\', '/', $name);
        $absolute =
            $normalized === '' || str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:/', $normalized) === 1;

        if ($absolute || in_array('..', explode('/', $normalized), true)) {
            throw new RuntimeException(sprintf('Archive entry "%s" escapes the target directory.', $name));
        }
    }

    private static function stripPrefix(string $prefix, string $value): string
    {
        return str_starts_with($value, $prefix) ? substr($value, strlen($prefix)) : $value;
    }
}

<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\ArchiveExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ArchiveExtractorTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . '/adp-archive-' . uniqid();
        mkdir($this->base . '/target', 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function entries(): iterable
    {
        yield 'parent traversal' => ['../evil.txt', false];
        yield 'nested traversal' => ['assets/../../evil.txt', false];
        yield 'absolute unix' => ['/etc/evil', false];
        yield 'absolute windows' => ['C:/evil.txt', false];
        yield 'backslash traversal' => ['..\\evil.txt', false];
        yield 'empty' => ['', false];
        yield 'file' => ['index.html', true];
        yield 'nested' => ['toolbar/bundle.js', true];
        yield 'directory' => ['assets/', true];
        yield 'dot in name' => ['assets/app..min.js', true];
        yield 'single dot segment' => ['./bundle.css', true];
    }

    #[DataProvider('entries')]
    public function testEntryNameGuard(string $name, bool $safe): void
    {
        if (!$safe) {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('escapes the target directory');
        }

        ArchiveExtractor::assertSafeEntry($name);
        $this->addToAssertionCount(1);
    }

    public function testExtractZipRefusesTraversalEntriesAndUnreadableArchives(): void
    {
        $zipPath = $this->base . '/evil.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('index.html', '<html>ok</html>');
        $zip->addFromString('../evil.txt', 'pwned');
        $zip->close();

        try {
            new ArchiveExtractor()->extractZip($zipPath, $this->base . '/target');
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('../evil.txt', $e->getMessage());
        }

        $this->assertFileDoesNotExist($this->base . '/evil.txt');
        $this->assertFileDoesNotExist($this->base . '/target/index.html');

        file_put_contents($this->base . '/bad.zip', 'not a zip');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to open zip archive');
        new ArchiveExtractor()->extractZip($this->base . '/bad.zip', $this->base . '/target');
    }

    public function testExtractZipExtractsSafeArchive(): void
    {
        $zipPath = $this->base . '/ok.zip';
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE);
        $zip->addFromString('index.html', '<html>ok</html>');
        $zip->addFromString('toolbar/bundle.js', 'js');
        $zip->close();

        new ArchiveExtractor()->extractZip($zipPath, $this->base . '/target');

        $this->assertSame('<html>ok</html>', file_get_contents($this->base . '/target/index.html'));
        $this->assertSame('js', file_get_contents($this->base . '/target/toolbar/bundle.js'));
    }

    public function testExtractTarGzExtractsSafeArchiveAndCleansUpIntermediateTar(): void
    {
        // Build under a different name: PharData keeps every opened archive in a
        // process-wide registry, and the extractor decompresses to `<archive>.tar`.
        $buildPath = $this->base . '/build.tar';
        $tar = new \PharData($buildPath);
        $tar->addFromString('index.html', '<html>tar</html>');
        $tar->addFromString('assets/app.css', 'css');
        $tar->compress(\Phar::GZ);
        unset($tar);
        unlink($buildPath);

        $archive = $this->base . '/ok.tar.gz';
        rename($buildPath . '.gz', $archive);

        new ArchiveExtractor()->extractTarGz($archive, $this->base . '/target');

        $this->assertSame('<html>tar</html>', file_get_contents($this->base . '/target/index.html'));
        $this->assertSame('css', file_get_contents($this->base . '/target/assets/app.css'));
        $this->assertFileDoesNotExist($archive . '.tar');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Tests\Unit\Command;

use AppDevPanel\Adapter\Symfony\Command\AssetsInstallCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Covers `app-dev-panel:assets:install` — copies (or symlinks) the FrontendAssets
 * dist into `<public-dir>/bundles/appdevpanel/` so the web server (nginx/Apache)
 * serves the panel + toolbar bundle directly.
 *
 * Each test provides its own fixture source directory via the `$sourceDir`
 * constructor argument, so we don't depend on whether `libs/FrontendAssets/dist/`
 * is populated in the monorepo checkout.
 */
final class AssetsInstallCommandTest extends TestCase
{
    private string $projectDir;
    private string $publicDir;
    private string $fixtureSource;

    protected function setUp(): void
    {
        $suffix = bin2hex(random_bytes(6));
        $this->projectDir = sys_get_temp_dir() . '/adp_install_project_' . $suffix;
        $this->publicDir = $this->projectDir . '/public';
        $this->fixtureSource = sys_get_temp_dir() . '/adp_install_fixture_' . $suffix;

        mkdir($this->publicDir, 0o755, true);
        mkdir($this->fixtureSource . '/toolbar', 0o755, true);
        file_put_contents($this->fixtureSource . '/index.html', '<!doctype html>');
        file_put_contents($this->fixtureSource . '/bundle.js', "console.log('panel');");
        file_put_contents($this->fixtureSource . '/bundle.css', 'body{}');
        file_put_contents($this->fixtureSource . '/toolbar/bundle.js', "console.log('toolbar');");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectDir);
        $this->removeDirectory($this->fixtureSource);
    }

    public function testFailsWhenSourceDistIsEmpty(): void
    {
        $emptySource = sys_get_temp_dir() . '/adp_install_empty_' . bin2hex(random_bytes(4));
        mkdir($emptySource, 0o755, true);

        try {
            $command = new AssetsInstallCommand($this->projectDir, null, $emptySource);
            $tester = new CommandTester($command);

            $exitCode = $tester->execute(['--public-dir' => $this->publicDir]);

            $this->assertSame(Command::FAILURE, $exitCode);
            $this->assertStringContainsString('frontend-assets is not installed', $tester->getDisplay());
        } finally {
            rmdir($emptySource);
        }
    }

    public function testCopiesBundlesIntoPublicBundlesAppdevpanel(): void
    {
        $command = new AssetsInstallCommand($this->projectDir, null, $this->fixtureSource);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute(['--public-dir' => $this->publicDir]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $target = $this->publicDir . '/' . AssetsInstallCommand::PUBLIC_SUBPATH;
        $this->assertDirectoryExists($target);
        $this->assertFileExists($target . '/index.html');
        $this->assertFileExists($target . '/bundle.js');
        $this->assertFileExists($target . '/toolbar/bundle.js');
        $this->assertStringContainsString('Installed ADP panel + toolbar assets', $tester->getDisplay());
    }

    public function testSymlinkModeCreatesLink(): void
    {
        if (\PHP_OS_FAMILY === 'Windows') {
            // Symlink creation on Windows requires elevated privileges the CI
            // runners don't have; the command falls back to copy there, which
            // is exercised by `testCopiesBundlesIntoPublicBundlesAppdevpanel`.
            $this->expectNotToPerformAssertions();
            return;
        }

        $command = new AssetsInstallCommand($this->projectDir, null, $this->fixtureSource);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--public-dir' => $this->publicDir,
            '--symlink' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $exitCode);

        $target = $this->publicDir . '/' . AssetsInstallCommand::PUBLIC_SUBPATH;
        $this->assertTrue(is_link($target), 'Expected symlink at ' . $target);
        $this->assertSame(realpath($this->fixtureSource), realpath($target));
    }

    public function testReinstallOverwritesPreviousPublish(): void
    {
        $target = $this->publicDir . '/' . AssetsInstallCommand::PUBLIC_SUBPATH;
        mkdir($target, 0o755, true);
        file_put_contents($target . '/stale-marker.txt', 'remnant of an older install');

        $command = new AssetsInstallCommand($this->projectDir, null, $this->fixtureSource);
        $tester = new CommandTester($command);

        $tester->execute(['--public-dir' => $this->publicDir]);

        $this->assertFileDoesNotExist(
            $target . '/stale-marker.txt',
            'Previous install artifacts must be removed before re-publishing.',
        );
        $this->assertFileExists($target . '/index.html');
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path) && !is_link($path)) {
            return;
        }
        if (is_link($path)) {
            unlink($path);
            return;
        }
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($items as $item) {
            if ($item->isLink() || $item->isFile()) {
                unlink($item->getPathname());
            } else {
                rmdir($item->getPathname());
            }
        }
        rmdir($path);
    }
}

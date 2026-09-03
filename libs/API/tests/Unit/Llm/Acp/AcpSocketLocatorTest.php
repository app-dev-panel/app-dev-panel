<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Llm\Acp;

use AppDevPanel\Api\Llm\Acp\AcpDaemonManager;
use AppDevPanel\Api\Llm\Acp\AcpSocketLocator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AcpSocketLocatorTest extends TestCase
{
    private string $storage;

    protected function setUp(): void
    {
        $this->storage = sys_get_temp_dir() . '/adp-acp-' . uniqid();
        mkdir($this->storage, 0o755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->storage);
    }

    public function testSocketLivesUnderStoragePathNotSystemTemp(): void
    {
        $locator = new AcpSocketLocator($this->storage . '/');

        $this->assertSame($this->storage . '/.acp', $locator->getSocketDirectory());
        $this->assertSame($this->storage . '/.acp/daemon.sock', $locator->getSocketPath());
        $this->assertStringNotContainsString(md5($this->storage), $locator->getSocketPath());
    }

    public function testFallsBackToPerUserTempDirOnlyWhenPathIsTooLongForUnixSockets(): void
    {
        $long = '/srv/' . str_repeat('very-long-directory-name/', 5) . 'runtime/debug';
        $locator = new AcpSocketLocator($long);

        $path = $locator->getSocketPath();
        $this->assertLessThanOrEqual(100, strlen($path));
        $this->assertStringStartsWith(sys_get_temp_dir() . '/adp-acp-', $path);
        $this->assertStringContainsString('/' . substr(md5($long), 0, 12) . '/daemon.sock', $path);
        // Different storage paths never share a socket.
        $this->assertNotSame($path, new AcpSocketLocator($long . '-other')->getSocketPath());
    }

    public function testEnsureSocketDirectoryCreatesPrivateDirectory(): void
    {
        $locator = new AcpSocketLocator($this->storage);

        $dir = $locator->ensureSocketDirectory();

        $this->assertDirectoryExists($dir);
        $this->assertSame('0700', substr(sprintf('%o', fileperms($dir)), -4));
    }

    public function testEnsureSocketDirectoryTightensLoosePermissions(): void
    {
        $dir = $this->storage . '/.acp';
        mkdir($dir, 0o777);
        chmod($dir, 0o777);

        new AcpSocketLocator($this->storage)->ensureSocketDirectory();
        clearstatcache(true, $dir);

        $this->assertSame('0700', substr(sprintf('%o', fileperms($dir)), -4));
    }

    public function testEnsureSocketDirectoryRefusesSymlink(): void
    {
        $target = $this->storage . '/elsewhere';
        mkdir($target, 0o700);
        symlink($target, $this->storage . '/.acp');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('symlink');
        new AcpSocketLocator($this->storage)->ensureSocketDirectory();
    }

    public function testEnsureSocketDirectoryRefusesRegularFile(): void
    {
        file_put_contents($this->storage . '/.acp', 'not a dir');

        $this->expectException(RuntimeException::class);
        new AcpSocketLocator($this->storage)->ensureSocketDirectory();
    }

    public function testAssertTrustedSocketRejectsPlantedRegularFile(): void
    {
        $locator = new AcpSocketLocator($this->storage);
        $locator->ensureSocketDirectory();
        file_put_contents($locator->getSocketPath(), 'planted');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not a Unix socket');
        $locator->assertTrustedSocket();
    }

    public function testAssertTrustedSocketRejectsWorldAccessibleDirectory(): void
    {
        $locator = new AcpSocketLocator($this->storage);
        $dir = $locator->ensureSocketDirectory();
        file_put_contents($locator->getSocketPath(), '');
        chmod($dir, 0o755);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('0700');
        $locator->assertTrustedSocket();
    }

    public function testDaemonManagerDelegatesToLocatorAndNeverConnectsToUntrustedNode(): void
    {
        $manager = new AcpDaemonManager($this->storage);

        $this->assertSame($this->storage . '/.acp/daemon.sock', $manager->getSocketPath());
        $this->assertSame($this->storage . '/.acp', $manager->getSocketDirectory());
        $this->assertSame($this->storage . '/.acp', $manager->ensureSocketDirectory());

        // A planted regular file where the socket should be: probes report "not running" ...
        file_put_contents($manager->getSocketPath(), 'planted');
        $this->assertFalse($manager->isRunning());
        $this->assertFalse($manager->isSessionActive('s1'));

        // ... and explicit operations refuse loudly instead of talking to it.
        try {
            $manager->startSession('s1', 'claude');
            $this->fail('Expected RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('not a Unix socket', $e->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not a Unix socket');
        $manager->sendPrompt('s1', [], '', 5.0);
    }

    public function testDaemonManagerReportsMissingSocketWithoutWarnings(): void
    {
        $manager = new AcpDaemonManager($this->storage);

        $this->assertFalse($manager->isRunning());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('does not exist');
        $manager->startSession('s1', 'claude');
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        chmod($dir, 0o755);
        foreach (scandir($dir) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . '/' . $item;
            if (is_link($path) || !is_dir($path)) {
                unlink($path);
            } else {
                $this->removeDir($path);
            }
        }
        rmdir($dir);
    }
}

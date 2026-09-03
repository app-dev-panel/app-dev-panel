<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\PathResolver;
use PHPUnit\Framework\TestCase;

final class PathResolverTest extends TestCase
{
    private string $base;

    protected function setUp(): void
    {
        $this->base = sys_get_temp_dir() . '/adp-path-resolver-' . uniqid('', true);
        mkdir($this->base . '/srv/app/config', 0o755, true);
        mkdir($this->base . '/srv/app-backup', 0o755, true);
        file_put_contents($this->base . '/srv/app/config/params.php', '<?php');
        file_put_contents($this->base . '/srv/app-backup/.env', 'SECRET=1');
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->base);
    }

    public function testGetters(): void
    {
        $resolver = new PathResolver('/root', '/root/runtime');

        $this->assertSame('/root', $resolver->getRootPath());
        $this->assertSame('/root/runtime', $resolver->getRuntimePath());
    }

    public function testIsInsideAcceptsRootItselfAndDescendants(): void
    {
        $root = $this->base . '/srv/app';

        $this->assertTrue(PathResolver::isInside($root, $root));
        $this->assertTrue(PathResolver::isInside($root . '/', $root));
        $this->assertTrue(PathResolver::isInside($root, $root . '/config'));
        $this->assertTrue(PathResolver::isInside($root, $root . '/config/params.php'));
        $this->assertTrue(PathResolver::isInside($root, $root . '/config/../config/params.php'));
    }

    public function testIsInsideRejectsSiblingWithSharedPrefix(): void
    {
        $root = $this->base . '/srv/app';

        // `/srv/app-backup/.env` starts with the string `/srv/app` but is not inside it.
        $this->assertFalse(PathResolver::isInside($root, $this->base . '/srv/app-backup/.env'));
        $this->assertFalse(PathResolver::isInside($root, $this->base . '/srv/app-backup'));
        $this->assertFalse(PathResolver::isInside($root, $this->base . '/srv'));
        $this->assertFalse(PathResolver::isInside($root, $root . '/../app-backup/.env'));
    }

    public function testIsInsideResolvesSymlinks(): void
    {
        $root = $this->base . '/srv/app';
        symlink($this->base . '/srv/app-backup', $root . '/escape');

        $this->assertFalse(PathResolver::isInside($root, $root . '/escape/.env'));
        $this->assertFalse(PathResolver::isInside($root, $root . '/escape'));
    }

    public function testIsInsideIsFalseForMissingPaths(): void
    {
        $root = $this->base . '/srv/app';

        $this->assertFalse(PathResolver::isInside($root, $root . '/missing.php'));
        $this->assertFalse(PathResolver::isInside($this->base . '/nope', $root));
    }

    public function testCanonicalFallsBackToInputForMissingPath(): void
    {
        $this->assertSame(realpath($this->base . '/srv/app'), PathResolver::canonical($this->base . '/srv/app/'));
        $this->assertSame('/definitely/missing', PathResolver::canonical('/definitely/missing'));
    }

    public function testStripPrefix(): void
    {
        $this->assertSame('/config/params.php', PathResolver::stripPrefix('/srv/app', '/srv/app/config/params.php'));
        $this->assertSame('/srv/app-backup/.env', PathResolver::stripPrefix('/srv/appx', '/srv/app-backup/.env'));
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
            if (is_link($path) || !is_dir($path)) {
                unlink($path);
            } else {
                $this->removeDir($path);
            }
        }
        rmdir($dir);
    }
}

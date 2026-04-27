<?php

declare(strict_types=1);

namespace AppDevPanel\FrontendAssets\Tests\Unit;

use AppDevPanel\FrontendAssets\FrontendAssets;
use PHPUnit\Framework\TestCase;

final class FrontendAssetsTest extends TestCase
{
    public function testPathPointsAtSiblingDistDirectory(): void
    {
        $this->assertSame(\dirname(__DIR__, 2) . '/dist', FrontendAssets::path());
    }

    public function testResolveReturnsLocalFallbackWhenPackageDistIsEmpty(): void
    {
        $local = $this->makeLocalFixture(['bundle.js' => 'console.log(1);']);

        try {
            // FrontendAssets::exists() reads dist/index.html on disk; in this monorepo
            // checkout the dist is gitignored, so the package side is empty and the
            // helper must fall back to the caller-provided directory.
            if (FrontendAssets::exists()) {
                $this->assertSame(FrontendAssets::path(), FrontendAssets::resolve($local));
            } else {
                $this->assertSame($local, FrontendAssets::resolve($local));
            }
        } finally {
            $this->cleanup($local);
        }
    }

    public function testResolveReturnsNullWhenLocalFallbackHasNoBundle(): void
    {
        if (FrontendAssets::exists()) {
            $this->assertSame(FrontendAssets::path(), FrontendAssets::resolve(null));
            return;
        }

        $this->assertNull(FrontendAssets::resolve(null));

        $emptyLocal = $this->makeLocalFixture([]);
        try {
            $this->assertNull(FrontendAssets::resolve($emptyLocal));
        } finally {
            $this->cleanup($emptyLocal);
        }
    }

    /**
     * @param array<string,string> $files relative-path => contents
     */
    private function makeLocalFixture(array $files): string
    {
        $dir = sys_get_temp_dir() . '/adp_frontend_assets_test_' . bin2hex(random_bytes(6));
        mkdir($dir, 0o755, true);
        foreach ($files as $name => $contents) {
            file_put_contents($dir . '/' . $name, $contents);
        }
        return $dir;
    }

    private function cleanup(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            unlink($dir . '/' . $entry);
        }
        rmdir($dir);
    }
}

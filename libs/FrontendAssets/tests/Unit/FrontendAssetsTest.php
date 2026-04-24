<?php

declare(strict_types=1);

namespace AppDevPanel\FrontendAssets\Tests\Unit;

use AppDevPanel\FrontendAssets\FrontendAssets;
use PHPUnit\Framework\TestCase;

final class FrontendAssetsTest extends TestCase
{
    public function testPathPointsAtDistDirectory(): void
    {
        $expected = \dirname(__DIR__, 2) . '/dist';
        self::assertSame($expected, FrontendAssets::path());
    }

    public function testToolbarPathIsNestedUnderDist(): void
    {
        self::assertSame(FrontendAssets::path() . '/toolbar', FrontendAssets::toolbarPath());
    }

    public function testExistsReflectsIndexHtmlPresence(): void
    {
        $index = FrontendAssets::path() . '/index.html';
        self::assertSame(is_file($index), FrontendAssets::exists());
    }

    public function testToolbarExistsReflectsBundleJsPresence(): void
    {
        $bundle = FrontendAssets::toolbarPath() . '/bundle.js';
        self::assertSame(is_file($bundle), FrontendAssets::toolbarExists());
    }
}

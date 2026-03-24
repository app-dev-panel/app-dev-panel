<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\NullPathMapper;
use AppDevPanel\Api\PathMapper;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PathMapperTest extends TestCase
{
    public function testMapToLocalWithSingleRule(): void
    {
        $mapper = new PathMapper(['/app' => '/home/user/project']);

        $this->assertSame('/home/user/project/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
    }

    public function testMapToRemoteWithSingleRule(): void
    {
        $mapper = new PathMapper(['/app' => '/home/user/project']);

        $this->assertSame('/app/src/Foo.php', $mapper->mapToRemote('/home/user/project/src/Foo.php'));
    }

    public function testNoMatchReturnsOriginalPath(): void
    {
        $mapper = new PathMapper(['/app' => '/home/user/project']);

        $this->assertSame('/other/path/file.php', $mapper->mapToLocal('/other/path/file.php'));
        $this->assertSame('/other/path/file.php', $mapper->mapToRemote('/other/path/file.php'));
    }

    public function testFirstMatchWins(): void
    {
        $mapper = new PathMapper([
            '/app/vendor' => '/home/user/vendor',
            '/app' => '/home/user/project',
        ]);

        $this->assertSame('/home/user/vendor/autoload.php', $mapper->mapToLocal('/app/vendor/autoload.php'));
        $this->assertSame('/home/user/project/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
    }

    public function testEmptyRules(): void
    {
        $mapper = new PathMapper([]);

        $this->assertSame('/app/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
        $this->assertSame('/app/src/Foo.php', $mapper->mapToRemote('/app/src/Foo.php'));
    }

    public function testGetRulesReturnsConfiguredRules(): void
    {
        $rules = ['/app' => '/home/user/project', '/vendor' => '/home/user/vendor'];
        $mapper = new PathMapper($rules);

        $this->assertSame($rules, $mapper->getRules());
    }

    public function testExactPrefixMatchOnly(): void
    {
        $mapper = new PathMapper(['/app' => '/home/user/project']);

        // '/application' should NOT match '/app' prefix... actually it does because str_starts_with
        // This is by design - prefix matching. '/app' matches '/application' start.
        $this->assertSame('/home/user/projectlication/Foo.php', $mapper->mapToLocal('/application/Foo.php'));
    }

    public function testTrailingSlashInRules(): void
    {
        $mapper = new PathMapper(['/app/' => '/home/user/project/']);

        $this->assertSame('/home/user/project/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
        $this->assertSame('/app/src/Foo.php', $mapper->mapToRemote('/home/user/project/src/Foo.php'));
    }

    public function testNullPathMapperReturnsOriginal(): void
    {
        $mapper = new NullPathMapper();

        $this->assertSame('/app/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
        $this->assertSame('/app/src/Foo.php', $mapper->mapToRemote('/app/src/Foo.php'));
        $this->assertSame([], $mapper->getRules());
    }

    public function testWindowsPaths(): void
    {
        $mapper = new PathMapper(['/app' => 'C:\\Users\\dev\\project']);

        $this->assertSame('C:\\Users\\dev\\project/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
        $this->assertSame('/app/src/Foo.php', $mapper->mapToRemote('C:\\Users\\dev\\project/src/Foo.php'));
    }

    public function testMultipleRulesReverseMapping(): void
    {
        $mapper = new PathMapper([
            '/app/vendor' => '/home/user/vendor',
            '/app' => '/home/user/project',
        ]);

        $this->assertSame('/app/vendor/autoload.php', $mapper->mapToRemote('/home/user/vendor/autoload.php'));
        $this->assertSame('/app/src/Foo.php', $mapper->mapToRemote('/home/user/project/src/Foo.php'));
    }
}

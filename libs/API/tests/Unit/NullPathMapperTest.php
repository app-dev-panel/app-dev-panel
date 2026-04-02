<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\NullPathMapper;
use PHPUnit\Framework\TestCase;

final class NullPathMapperTest extends TestCase
{
    public function testMapToLocalReturnsPathUnchanged(): void
    {
        $mapper = new NullPathMapper();

        $this->assertSame('/app/src/Foo.php', $mapper->mapToLocal('/app/src/Foo.php'));
    }

    public function testMapToRemoteReturnsPathUnchanged(): void
    {
        $mapper = new NullPathMapper();

        $this->assertSame('/home/user/project/Foo.php', $mapper->mapToRemote('/home/user/project/Foo.php'));
    }

    public function testGetRulesReturnsEmptyArray(): void
    {
        $mapper = new NullPathMapper();

        $this->assertSame([], $mapper->getRules());
    }

    public function testMapToLocalWithEmptyString(): void
    {
        $mapper = new NullPathMapper();

        $this->assertSame('', $mapper->mapToLocal(''));
    }

    public function testMapToRemoteWithEmptyString(): void
    {
        $mapper = new NullPathMapper();

        $this->assertSame('', $mapper->mapToRemote(''));
    }
}

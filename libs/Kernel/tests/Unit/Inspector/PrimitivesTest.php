<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Inspector;

use AppDevPanel\Kernel\Inspector\Primitives;
use PHPUnit\Framework\TestCase;

final class PrimitivesTest extends TestCase
{
    public function testDumpScalarsPassesThrough(): void
    {
        $this->assertSame(42, Primitives::dump(42));
        $this->assertSame('hello', Primitives::dump('hello'));
        $this->assertSame(true, Primitives::dump(true));
        $this->assertNull(Primitives::dump(null));
    }

    public function testDumpTopLevelClosureEmitsDescriptor(): void
    {
        $closure = static fn(): int => 1;

        $result = Primitives::dump($closure);

        $this->assertIsArray($result);
        $this->assertTrue($result['__closure']);
        $this->assertIsString($result['source']);
        $this->assertStringContainsString('fn', $result['source']);
        $this->assertSame(__FILE__, $result['file']);
    }

    public function testDumpClosureInsideArrayIsReplacedByDescriptor(): void
    {
        $closure = static fn(int $x): int => $x + 1;

        $result = Primitives::dump(['definition' => $closure, 'name' => 'X']);

        $this->assertIsArray($result);
        $this->assertSame('X', $result['name']);
        $this->assertIsArray($result['definition']);
        $this->assertTrue($result['definition']['__closure']);
        $this->assertIsString($result['definition']['source']);
        $this->assertSame(__FILE__, $result['definition']['file']);
    }

    public function testDumpNestedClosures(): void
    {
        $outer = static fn(): int => 1;
        $inner = static fn(): int => 2;

        $result = Primitives::dump([
            'group' => ['definition' => $outer, 'reset' => $inner],
        ]);

        $this->assertTrue($result['group']['definition']['__closure']);
        $this->assertTrue($result['group']['reset']['__closure']);
        $this->assertIsString($result['group']['definition']['source']);
        $this->assertIsString($result['group']['reset']['source']);
    }

    public function testDumpPlainArraysUnchanged(): void
    {
        $data = ['a' => 1, 'b' => ['nested' => 'value'], 'c' => [1, 2, 3]];

        $this->assertSame($data, Primitives::dump($data));
    }
}

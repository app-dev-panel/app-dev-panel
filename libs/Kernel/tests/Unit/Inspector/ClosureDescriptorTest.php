<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Inspector;

use AppDevPanel\Kernel\Inspector\ClosureDescriptor;
use PHPUnit\Framework\TestCase;

final class ClosureDescriptorTest extends TestCase
{
    public function testDescribeShortArrowFunction(): void
    {
        $closure = static fn(int $x): int => $x * 2;

        $result = ClosureDescriptor::describe($closure);

        $this->assertTrue($result['__closure']);
        $this->assertIsString($result['source']);
        $this->assertStringContainsString('fn', $result['source']);
        $this->assertSame(__FILE__, $result['file']);
        $this->assertIsInt($result['startLine']);
        $this->assertGreaterThan(0, $result['startLine']);
    }

    public function testDescribeMultiLineFunction(): void
    {
        $closure = static function (string $name): string {
            return 'Hello, ' . $name . '!';
        };

        $result = ClosureDescriptor::describe($closure);

        $this->assertTrue($result['__closure']);
        $this->assertStringContainsString('function', $result['source']);
        $this->assertGreaterThan($result['startLine'], $result['endLine']);
    }
}

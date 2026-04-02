<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit\Inspector;

use AppDevPanel\Kernel\Inspector\ClosureDescriptorTrait;
use PHPUnit\Framework\TestCase;

final class ClosureDescriptorTraitTest extends TestCase
{
    public function testDescribeClosureReturnsExpectedStructure(): void
    {
        $helper = new class() {
            use ClosureDescriptorTrait;

            public function describe(\Closure $closure): array
            {
                return self::describeClosure($closure);
            }
        };

        $closure = static fn(int $x): int => $x * 2;
        $result = $helper->describe($closure);

        $this->assertTrue($result['__closure']);
        $this->assertIsString($result['source']);
        $this->assertStringContainsString('fn', $result['source']);
        $this->assertSame(__FILE__, $result['file']);
        $this->assertIsInt($result['startLine']);
        $this->assertIsInt($result['endLine']);
        $this->assertGreaterThan(0, $result['startLine']);
    }

    public function testDescribeMultiLineClosure(): void
    {
        $helper = new class() {
            use ClosureDescriptorTrait;

            public function describe(\Closure $closure): array
            {
                return self::describeClosure($closure);
            }
        };

        $closure = static function (string $name): string {
            return 'Hello, ' . $name . '!';
        };

        $result = $helper->describe($closure);

        $this->assertTrue($result['__closure']);
        $this->assertIsString($result['source']);
        $this->assertSame(__FILE__, $result['file']);
        $this->assertGreaterThan($result['startLine'], $result['endLine']);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Tests\Unit;

use AppDevPanel\Kernel\ProxyDecoratedCalls;
use PHPUnit\Framework\TestCase;

final class ProxyDecoratedCallsTest extends TestCase
{
    public function testGet(): void
    {
        $decorated = new class {
            public string $name = 'test';
        };

        $proxy = $this->createProxy($decorated);

        $this->assertSame('test', $proxy->name);
    }

    public function testSet(): void
    {
        $decorated = new class {
            public string $name = 'original';
        };

        $proxy = $this->createProxy($decorated);
        $proxy->name = 'modified';

        $this->assertSame('modified', $decorated->name);
    }

    public function testCall(): void
    {
        $decorated = new class {
            public function greet(string $name): string
            {
                return "Hello, $name!";
            }
        };

        $proxy = $this->createProxy($decorated);

        $this->assertSame('Hello, World!', $proxy->greet('World'));
    }

    private function createProxy(object $decorated): object
    {
        return new class($decorated) {
            use ProxyDecoratedCalls;

            public function __construct(
                private readonly object $decorated,
            ) {}
        };
    }
}

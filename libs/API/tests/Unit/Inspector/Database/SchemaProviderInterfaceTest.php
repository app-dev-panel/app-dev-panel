<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector\Database;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use PHPUnit\Framework\TestCase;

final class SchemaProviderInterfaceTest extends TestCase
{
    public function testDefaultLimitConstant(): void
    {
        $this->assertSame(50, SchemaProviderInterface::DEFAULT_LIMIT);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Exception;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use PHPUnit\Framework\TestCase;

final class NotFoundExceptionTest extends TestCase
{
    public function testIsException(): void
    {
        $exception = new NotFoundException('Not found');
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertSame('Not found', $exception->getMessage());
    }

    public function testWithCode(): void
    {
        $exception = new NotFoundException('msg', 404);
        $this->assertSame(404, $exception->getCode());
    }
}

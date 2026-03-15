<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Debug\Middleware;

use AppDevPanel\Api\Debug\Middleware\MiddlewareDispatcherMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareDispatcherMiddlewareTest extends TestCase
{
    public function testImplementsMiddlewareInterface(): void
    {
        $this->assertTrue(is_subclass_of(MiddlewareDispatcherMiddleware::class, MiddlewareInterface::class));
    }
}

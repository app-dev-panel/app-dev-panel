<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\PathResolver;
use PHPUnit\Framework\TestCase;

final class PathResolverTest extends TestCase
{
    public function testGetRootPath(): void
    {
        $resolver = new PathResolver('/var/www/app', '/tmp/runtime');

        $this->assertSame('/var/www/app', $resolver->getRootPath());
    }

    public function testGetRuntimePath(): void
    {
        $resolver = new PathResolver('/var/www/app', '/tmp/runtime');

        $this->assertSame('/tmp/runtime', $resolver->getRuntimePath());
    }
}

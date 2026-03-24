<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\ApiExtensionsConfig;
use PHPUnit\Framework\TestCase;

final class ApiExtensionsConfigTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = new ApiExtensionsConfig();

        $this->assertSame([], $config->middlewares);
        $this->assertSame([], $config->commandMap);
        $this->assertSame([], $config->params);
    }

    public function testCustomValues(): void
    {
        $config = new ApiExtensionsConfig(
            middlewares: ['App\Middleware\Custom'],
            commandMap: ['test' => ['run' => 'App\Command\TestRun']],
            params: ['debug' => true],
        );

        $this->assertSame(['App\Middleware\Custom'], $config->middlewares);
        $this->assertSame(['test' => ['run' => 'App\Command\TestRun']], $config->commandMap);
        $this->assertSame(['debug' => true], $config->params);
    }
}

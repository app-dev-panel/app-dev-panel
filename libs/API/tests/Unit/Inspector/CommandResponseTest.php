<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Api\Tests\Unit\Inspector;

use PHPUnit\Framework\TestCase;
use AppDevPanel\Adapter\Yiisoft\Api\Inspector\CommandResponse;

final class CommandResponseTest extends TestCase
{
    public function testStatus(): void
    {
        $response = new CommandResponse(CommandResponse::STATUS_OK, 'result', ['errors']);

        $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
        $this->assertSame('result', $response->getResult());
        $this->assertSame(['errors'], $response->getErrors());
    }
}

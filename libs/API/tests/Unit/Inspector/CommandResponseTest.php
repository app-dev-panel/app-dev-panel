<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Inspector;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Api\Inspector\CommandResponse;

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

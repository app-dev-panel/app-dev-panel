<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Inspector\Command;

use PHPUnit\Framework\TestCase;
use Yiisoft\Aliases\Aliases;
use Yiisoft\Yii\Debug\Api\Inspector\Command\BashCommand;
use Yiisoft\Yii\Debug\Api\Inspector\CommandResponse;

final class BashCommandTest extends TestCase
{
    public function testSuccess(): void
    {
        $aliases = new Aliases([
            '@root' => __DIR__,
        ]);
        $command = new BashCommand($aliases, ['echo', 'test']);

        $response = $command->run();

        $this->assertSame(CommandResponse::STATUS_OK, $response->getStatus());
        $this->assertSame('test', $response->getResult());
        $this->assertSame([], $response->getErrors());
    }

    public function testError(): void
    {
        $aliases = new Aliases([
            '@root' => dirname(__DIR__, 3) . '/Support/Application',
        ]);
        $command = new BashCommand($aliases, ['bash', 'fail.sh', '1']);

        $response = $command->run();

        $this->assertSame(CommandResponse::STATUS_ERROR, $response->getStatus());
        $this->assertSame('failed', $response->getResult());
        $this->assertSame([], $response->getErrors());
    }
}

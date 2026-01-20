<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Unit\Inspector;

use PHPUnit\Framework\TestCase;
use Yiisoft\Yii\Debug\Api\Inspector\ApplicationState;

final class ApplicationStateTest extends TestCase
{
    public function testStatus(): void
    {
        $this->assertEquals([], ApplicationState::$params);

        ApplicationState::$params = ['key' => 'value'];
        $this->assertEquals(['key' => 'value'], ApplicationState::$params);
    }
}

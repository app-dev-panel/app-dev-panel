<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit\Inspector;

use PHPUnit\Framework\TestCase;
use AppDevPanel\Api\Inspector\ApplicationState;

final class ApplicationStateTest extends TestCase
{
    public function testStatus(): void
    {
        $this->assertEquals([], ApplicationState::$params);

        ApplicationState::$params = ['key' => 'value'];
        $this->assertEquals(['key' => 'value'], ApplicationState::$params);
    }
}

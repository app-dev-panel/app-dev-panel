<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Yii2\Inspector\Yii2ActionResolver;
use PHPUnit\Framework\TestCase;

final class Yii2ActionResolverTest extends TestCase
{
    public function testReturnsNullWhenNoApp(): void
    {
        // Ensure Yii::$app is null (default in unit tests)
        $original = \Yii::$app;
        \Yii::$app = null;

        try {
            $result = Yii2ActionResolver::resolve('site/index');
            $this->assertNull($result);
        } finally {
            \Yii::$app = $original;
        }
    }
}

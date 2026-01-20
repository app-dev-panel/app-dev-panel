<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Tests\Unit\Support;

use stdClass;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;

final class DummyCollector implements CollectorInterface
{
    private bool $isActive = false;

    public function getName(): string
    {
        return self::class;
    }

    public function startup(): void
    {
        $this->isActive = true;
    }

    public function shutdown(): void
    {
        $this->isActive = false;
    }

    public function getCollected(): array
    {
        if (!$this->isActive) {
            return [];
        }
        return [
            'int' => 123,
            'str' => 'asdas',
            'object' => new stdClass(),
        ];
    }
}

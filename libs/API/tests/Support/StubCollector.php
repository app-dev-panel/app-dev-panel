<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Tests\Support;

use Yiisoft\Yii\Debug\Collector\CollectorInterface;

final class StubCollector implements CollectorInterface
{
    public function __construct(private array $data = [])
    {
    }

    public function getName(): string
    {
        return 'stub';
    }

    public function startup(): void
    {
    }

    public function shutdown(): void
    {
    }

    public function getCollected(): array
    {
        return $this->data;
    }
}

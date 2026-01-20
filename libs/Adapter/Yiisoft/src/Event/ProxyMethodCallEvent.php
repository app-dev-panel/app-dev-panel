<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Event;

final class ProxyMethodCallEvent
{
    public function __construct(public string $service, public string $class, public string $methodName, public ?array $arguments, public mixed $result, public string $status, public ?object $error, public float $timeStart, public float $timeEnd)
    {
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Event;

final class ProxyMethodCallEvent
{
    public readonly string $service;
    public readonly string $class;
    public readonly string $methodName;
    public readonly ?array $arguments;
    public readonly mixed $result;
    public readonly string $status;
    public readonly ?object $error;
    public readonly float $timeStart;
    public readonly float $timeEnd;

    public function __construct(
        public readonly MethodCallRecord $record,
    ) {
        $this->service = $record->service;
        $this->class = $record->class;
        $this->methodName = $record->methodName;
        $this->arguments = $record->arguments;
        $this->result = $record->result;
        $this->status = $record->status;
        $this->error = $record->error;
        $this->timeStart = $record->timeStart;
        $this->timeEnd = $record->timeEnd;
    }
}

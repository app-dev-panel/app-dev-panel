<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector;

class CommandResponse
{
    public const STATUS_OK = 'ok';
    public const STATUS_ERROR = 'error';
    public const STATUS_FAIL = 'fail';

    public function __construct(
        private string $status,
        private mixed $result,
        private array $errors = [],
    ) {
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getResult(): mixed
    {
        return $this->result;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}

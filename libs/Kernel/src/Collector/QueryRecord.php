<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

final readonly class QueryRecord
{
    public function __construct(
        public string $sql,
        public string $rawSql,
        public array $params,
        public string $line,
        public float $startTime,
        public float $endTime,
        public int $rowsNumber = 0,
    ) {}
}

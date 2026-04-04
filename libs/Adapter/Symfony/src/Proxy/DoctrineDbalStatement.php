<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Doctrine\DBAL\Driver\Middleware\AbstractStatementMiddleware;
use Doctrine\DBAL\Driver\Result;
use Doctrine\DBAL\Driver\Statement;
use Doctrine\DBAL\ParameterType;

/**
 * DBAL statement wrapper that captures bound parameters and execution timing.
 */
final class DoctrineDbalStatement extends AbstractStatementMiddleware
{
    /** @var array<int|string, mixed> */
    private array $params = [];

    public function __construct(
        Statement $statement,
        private readonly DatabaseCollector $collector,
        private readonly string $sql,
    ) {
        parent::__construct($statement);
    }

    public function bindValue(int|string $param, mixed $value, ParameterType $type = ParameterType::STRING): void
    {
        $this->params[$param] = $value;
        parent::bindValue($param, $value, $type);
    }

    public function execute(): Result
    {
        $line = $this->getCallerLine();
        $id = uniqid('dbal_', true);

        $this->collector->collectQueryStart($id, $this->sql, $this->buildRawSql(), $this->params, $line);

        try {
            $result = parent::execute();
        } catch (\Throwable $e) {
            $this->collector->collectQueryError($id, $e);

            throw $e;
        }

        $this->collector->collectQueryEnd($id, $result->rowCount());

        return $result;
    }

    private function buildRawSql(): string
    {
        if ($this->params === []) {
            return $this->sql;
        }

        $raw = $this->sql;
        foreach ($this->params as $key => $value) {
            $valueStr = match (true) {
                is_null($value) => 'NULL',
                is_bool($value) => $value ? 'TRUE' : 'FALSE',
                is_int($value), is_float($value) => (string) $value,
                default => "'" . addslashes((string) $value) . "'",
            };

            if (is_int($key)) {
                $pos = strpos($raw, '?');
                if ($pos !== false) {
                    $raw = substr_replace($raw, $valueStr, $pos, 1);
                }
            } else {
                $raw = str_replace(':' . $key, $valueStr, $raw);
            }
        }

        return $raw;
    }

    private function getCallerLine(): string
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);

        foreach ($trace as $frame) {
            if (!isset($frame['file'])) {
                continue;
            }

            if (str_contains($frame['file'], 'vendor/') || str_contains($frame['file'], 'DoctrineDbal')) {
                continue;
            }

            return $frame['file'] . ':' . ($frame['line'] ?? 0);
        }

        return '';
    }
}

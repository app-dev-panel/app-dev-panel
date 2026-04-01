<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Inspect database: list tables, view schema, execute queries.
 *
 * Yii 2 equivalent of the Symfony Console-based InspectDatabaseCommand from libs/Cli.
 */
final class InspectDatabaseController extends Controller
{
    public $defaultAction = 'tables';

    public function __construct(
        $id,
        $module,
        private readonly SchemaProviderInterface $schemaProvider,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * List all database tables.
     *
     * @param bool $json Output raw JSON.
     */
    public function actionTables(bool $json = false): int
    {
        $tables = $this->schemaProvider->getTables();

        if ($json) {
            $this->outputJson($tables);
            return ExitCode::OK;
        }

        if ($tables === []) {
            Console::stdout(Console::ansiFormat("No tables found.\n", [Console::FG_YELLOW]));
            return ExitCode::OK;
        }

        Console::stdout(Console::ansiFormat("Database Tables\n", [Console::BOLD]));
        Console::stdout(str_repeat('=', 60) . "\n");
        Console::stdout(Console::ansiFormat(sprintf("%-40s  %-10s  %s\n", 'Table', 'Rows', 'Size'), [Console::BOLD]));
        Console::stdout(str_repeat('-', 60) . "\n");

        foreach ($tables as $table) {
            if (!is_array($table)) {
                continue;
            }
            Console::stdout(sprintf(
                "%-40s  %-10s  %s\n",
                (string) ($table['name'] ?? '—'),
                (string) ($table['rows'] ?? '—'),
                (string) ($table['size'] ?? '—'),
            ));
        }

        return ExitCode::OK;
    }

    /**
     * View table schema and records.
     *
     * @param string $name Table name.
     * @param int $limit Row limit.
     * @param int $offset Row offset.
     * @param bool $json Output raw JSON.
     */
    public function actionTable(string $name, int $limit = 50, int $offset = 0, bool $json = false): int
    {
        $data = $this->schemaProvider->getTable($name, $limit, $offset);

        if ($json) {
            $this->outputJson($data);
            return ExitCode::OK;
        }

        Console::stdout(Console::ansiFormat(sprintf("Table: %s\n", $name), [Console::BOLD]));
        Console::stdout(str_repeat('=', 60) . "\n");
        $this->outputJson($data);

        return ExitCode::OK;
    }

    /**
     * Execute a SQL query.
     *
     * @param string $sql SQL query to execute.
     * @param bool $json Output raw JSON.
     */
    public function actionQuery(string $sql, bool $json = false): int
    {
        if ($sql === '') {
            Console::stderr(Console::ansiFormat("SQL query is required.\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $result = $this->schemaProvider->executeQuery($sql);
        } catch (\Throwable $e) {
            Console::stderr(Console::ansiFormat($e->getMessage() . "\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->outputJson($result);
        return ExitCode::OK;
    }

    /**
     * Explain a SQL query.
     *
     * @param string $sql SQL query to explain.
     * @param bool $analyze Use EXPLAIN ANALYZE.
     * @param bool $json Output raw JSON.
     */
    public function actionExplain(string $sql, bool $analyze = false, bool $json = false): int
    {
        if ($sql === '') {
            Console::stderr(Console::ansiFormat("SQL query is required.\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        try {
            $result = $this->schemaProvider->explainQuery($sql, [], $analyze);
        } catch (\Throwable $e) {
            Console::stderr(Console::ansiFormat($e->getMessage() . "\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $this->outputJson($result);
        return ExitCode::OK;
    }

    private function outputJson(mixed $data): void
    {
        Console::stdout(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}

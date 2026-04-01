<?php

declare(strict_types=1);

namespace AppDevPanel\Cli\Command;

use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'inspect:db', description: 'Inspect database: list tables, view schema, execute queries')]
final class InspectDatabaseCommand extends Command
{
    public function __construct(
        private readonly SchemaProviderInterface $schemaProvider,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('action', InputArgument::OPTIONAL, 'Action: tables, table, query, explain', 'tables')
            ->addArgument('target', InputArgument::OPTIONAL, 'Table name or SQL query')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Row limit for table view', '50')
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Row offset for table view', '0')
            ->addOption('analyze', null, InputOption::VALUE_NONE, 'Use EXPLAIN ANALYZE instead of EXPLAIN')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output raw JSON')
            ->setHelp(<<<'HELP'
                Inspect database schema and execute queries.

                List all tables:
                  <info>inspect:db tables</info>

                View table schema and records:
                  <info>inspect:db table users</info>
                  <info>inspect:db table users --limit=10 --offset=20</info>

                Execute a SQL query:
                  <info>inspect:db query "SELECT * FROM users WHERE active = 1"</info>

                Explain a SQL query:
                  <info>inspect:db explain "SELECT * FROM users WHERE active = 1"</info>
                  <info>inspect:db explain "SELECT * FROM users" --analyze</info>
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $action = (string) $input->getArgument('action');

        return match ($action) {
            'tables' => $this->listTables($input, $output, $io),
            'table' => $this->viewTable($input, $output, $io),
            'query' => $this->executeQuery($input, $output, $io),
            'explain' => $this->explainQuery($input, $output, $io),
            default => $this->handleUnknownAction($io, $action),
        };
    }

    private function listTables(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $tables = $this->schemaProvider->getTables();
        $json = (bool) $input->getOption('json');

        if ($json) {
            $this->writeJson($output, $tables);
            return Command::SUCCESS;
        }

        if ($tables === []) {
            $io->info('No tables found.');
            return Command::SUCCESS;
        }

        $io->title('Database Tables');

        $rows = [];
        foreach ($tables as $table) {
            if (!is_array($table)) {
                continue;
            }
            $rows[] = [
                (string) ($table['name'] ?? '—'),
                (string) ($table['rows'] ?? '—'),
                (string) ($table['size'] ?? '—'),
            ];
        }

        $io->table(['Table', 'Rows', 'Size'], $rows);

        return Command::SUCCESS;
    }

    private function viewTable(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $tableName = $input->getArgument('target');
        if (!is_string($tableName) || $tableName === '') {
            $io->error('Table name is required. Usage: inspect:db table <name>');
            return Command::FAILURE;
        }

        $limit = (int) $input->getOption('limit');
        $offset = (int) $input->getOption('offset');
        $json = (bool) $input->getOption('json');

        $data = $this->schemaProvider->getTable($tableName, $limit, $offset);

        if ($json) {
            $this->writeJson($output, $data);
            return Command::SUCCESS;
        }

        $io->title(sprintf('Table: %s', $tableName));

        if (isset($data['columns']) && is_array($data['columns'])) {
            $io->section('Schema');
            $columnRows = [];
            foreach ($data['columns'] as $column) {
                if (!is_array($column)) {
                    continue;
                }
                $columnRows[] = [
                    (string) ($column['name'] ?? '—'),
                    (string) ($column['type'] ?? '—'),
                    $column['allowNull'] ?? false ? 'YES' : 'NO',
                    (string) ($column['defaultValue'] ?? '—'),
                    $column['isPrimaryKey'] ?? false ? 'YES' : '',
                ];
            }
            $io->table(['Column', 'Type', 'Nullable', 'Default', 'PK'], $columnRows);
        }

        if (isset($data['records']) && is_array($data['records']) && $data['records'] !== []) {
            $io->section(sprintf('Records (limit: %d, offset: %d)', $limit, $offset));
            $this->writeJson($output, $data['records']);
        }

        return Command::SUCCESS;
    }

    private function executeQuery(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $sql = $input->getArgument('target');
        if (!is_string($sql) || $sql === '') {
            $io->error('SQL query is required. Usage: inspect:db query "SELECT ..."');
            return Command::FAILURE;
        }

        $json = (bool) $input->getOption('json');

        try {
            $result = $this->schemaProvider->executeQuery($sql);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($json) {
            $this->writeJson($output, $result);
            return Command::SUCCESS;
        }

        if (isset($result['rows']) && is_array($result['rows']) && $result['rows'] !== []) {
            $first = $result['rows'][0];
            if (is_array($first)) {
                $headers = array_map(strval(...), array_keys($first));
                $rows = array_map(static fn(mixed $row): array => (
                    is_array($row)
                        ? array_map(static fn(mixed $v): string => is_scalar($v)
                        || $v === null
                            ? (string) ($v ?? 'NULL')
                            : json_encode($v, JSON_THROW_ON_ERROR), $row)
                        : []
                ), $result['rows']);
                $io->table($headers, $rows);
            } else {
                $this->writeJson($output, $result);
            }
        } else {
            $this->writeJson($output, $result);
        }

        if (isset($result['count'])) {
            $io->text(sprintf('Rows affected: %s', (string) $result['count']));
        }

        return Command::SUCCESS;
    }

    private function explainQuery(InputInterface $input, OutputInterface $output, SymfonyStyle $io): int
    {
        $sql = $input->getArgument('target');
        if (!is_string($sql) || $sql === '') {
            $io->error('SQL query is required. Usage: inspect:db explain "SELECT ..."');
            return Command::FAILURE;
        }

        $analyze = (bool) $input->getOption('analyze');
        $json = (bool) $input->getOption('json');

        try {
            $result = $this->schemaProvider->explainQuery($sql, [], $analyze);
        } catch (\Throwable $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }

        if ($json) {
            $this->writeJson($output, $result);
            return Command::SUCCESS;
        }

        $io->title(sprintf('EXPLAIN%s', $analyze ? ' ANALYZE' : ''));
        $this->writeJson($output, $result);

        return Command::SUCCESS;
    }

    private function handleUnknownAction(SymfonyStyle $io, string $action): int
    {
        $io->error(sprintf('Unknown action "%s". Available: tables, table, query, explain', $action));
        return Command::FAILURE;
    }

    private function writeJson(OutputInterface $output, array $data): void
    {
        $output->writeln(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Query stored debug data from the CLI.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugQueryCommand from libs/Cli.
 */
final class DebugQueryController extends Controller
{
    public $defaultAction = 'list';

    public function __construct(
        $id,
        $module,
        private readonly CollectorRepositoryInterface $collectorRepository,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * List recent debug entries.
     *
     * @param int $limit Maximum entries to show.
     * @param bool $json Output raw JSON.
     */
    public function actionList(int $limit = 20, bool $json = false): int
    {
        $entries = $this->collectorRepository->getSummary();

        if ($entries === []) {
            $this->writeColored("No debug entries found.\n", Console::FG_YELLOW);
            return ExitCode::OK;
        }

        $entries = array_slice($entries, 0, $limit);

        if ($json) {
            Console::stdout(json_encode($entries, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            return ExitCode::OK;
        }

        $this->writeColored(sprintf("Debug Entries (showing %d)\n", count($entries)), Console::BOLD);
        Console::stdout(str_repeat('=', 60) . "\n");

        $rows = [];
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $rows[] = [
                (string) ($entry['id'] ?? '—'),
                $this->extractRequestInfo($entry, 'method', 'GET'),
                $this->extractRequestInfo($entry, 'url', '—'),
                $this->extractRequestInfo($entry, 'responseStatusCode', '—'),
                $this->formatCollectors($entry),
            ];
        }

        $this->writeColored(sprintf(
            "%-36s  %-6s  %-30s  %-6s  %s\n",
            'ID',
            'Method',
            'URL',
            'Status',
            'Collectors',
        ), Console::BOLD);
        Console::stdout(str_repeat('-', 100) . "\n");

        foreach ($rows as $row) {
            Console::stdout(sprintf(
                "%-36s  %-6s  %-30s  %-6s  %s\n",
                $row[0],
                $row[1],
                mb_substr($row[2], 0, 30),
                $row[3],
                $row[4],
            ));
        }

        return ExitCode::OK;
    }

    /**
     * View full data for a debug entry.
     *
     * @param string $id Debug entry ID.
     * @param string|null $collector Collector class name to filter by.
     * @param bool $json Output raw JSON.
     */
    public function actionView(string $id, ?string $collector = null, bool $json = false): int
    {
        try {
            $data = $this->collectorRepository->getDetail($id);
        } catch (\Throwable $e) {
            Console::stderr(Console::ansiFormat($e->getMessage() . "\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($collector !== null) {
            if (!array_key_exists($collector, $data)) {
                Console::stderr(Console::ansiFormat(
                    sprintf("Collector \"%s\" not found in entry \"%s\".\n", $collector, $id),
                    [Console::FG_RED],
                ));
                Console::stderr("Available collectors:\n");
                foreach (array_keys($data) as $key) {
                    Console::stderr(sprintf("  - %s\n", (string) $key));
                }
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $data = is_array($data[$collector]) ? $data[$collector] : [];
        }

        if ($json) {
            Console::stdout(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            return ExitCode::OK;
        }

        if ($collector !== null) {
            $this->writeColored(sprintf("Collector: %s (Entry: %s)\n", $collector, $id), Console::BOLD);
            Console::stdout(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
            return ExitCode::OK;
        }

        $this->writeColored(sprintf("Debug Entry: %s\n", $id), Console::BOLD);
        Console::stdout(str_repeat('=', 60) . "\n");

        foreach ($data as $collectorName => $collectorData) {
            $this->writeColored(sprintf("\n[%s]\n", (string) $collectorName), Console::BOLD, Console::FG_CYAN);
            if (is_array($collectorData) && $collectorData !== []) {
                Console::stdout(json_encode(
                    $collectorData,
                    JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
                ) . "\n");
            } else {
                $this->writeColored("(empty)\n", Console::FG_GREY);
            }
        }

        return ExitCode::OK;
    }

    private function writeColored(string $text, int ...$formats): void
    {
        Console::stdout(Console::ansiFormat($text, $formats));
    }

    private function extractRequestInfo(array $entry, string $key, string $default): string
    {
        foreach (['request', 'web', 'command'] as $summaryKey) {
            if (
                array_key_exists($summaryKey, $entry)
                && is_array($entry[$summaryKey])
                && array_key_exists($key, $entry[$summaryKey])
            ) {
                return (string) $entry[$summaryKey][$key];
            }
        }

        return $default;
    }

    private function formatCollectors(array $entry): string
    {
        $parts = [];
        $loggerTotal = (int) ($entry['logger']['total'] ?? 0);
        if ($loggerTotal > 0) {
            $parts[] = sprintf('logs:%d', $loggerTotal);
        }
        $eventTotal = (int) ($entry['event']['total'] ?? 0);
        if ($eventTotal > 0) {
            $parts[] = sprintf('events:%d', $eventTotal);
        }
        if (
            array_key_exists('exception', $entry)
            && is_array($entry['exception'])
            && array_key_exists('class', $entry['exception'])
        ) {
            $parts[] = 'exception';
        }
        $timelineTotal = (int) ($entry['timeline']['total'] ?? 0);
        if ($timelineTotal > 0) {
            $parts[] = sprintf('timeline:%d', $timelineTotal);
        }

        return $parts !== [] ? implode(', ', $parts) : '—';
    }
}

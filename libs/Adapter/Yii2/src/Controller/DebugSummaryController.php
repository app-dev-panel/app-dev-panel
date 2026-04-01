<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Show brief summary of a debug entry.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugSummaryCommand from libs/Cli.
 */
final class DebugSummaryController extends Controller
{
    public function __construct(
        $id,
        $module,
        private readonly CollectorRepositoryInterface $collectorRepository,
        $config = [],
    ) {
        parent::__construct($id, $module, $config);
    }

    /**
     * Show brief summary of a debug entry.
     *
     * @param string $id Debug entry ID.
     * @param bool $json Output raw JSON.
     */
    public function actionIndex(string $id, bool $json = false): int
    {
        try {
            $data = $this->collectorRepository->getSummary($id);
        } catch (\Throwable $e) {
            Console::stderr(Console::ansiFormat($e->getMessage() . "\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($json) {
            $this->outputJson($data);
            return ExitCode::OK;
        }

        Console::stdout(Console::ansiFormat(sprintf("Debug Entry Summary: %s\n", $id), [Console::BOLD]));
        Console::stdout(str_repeat('=', 60) . "\n");

        $this->renderRequestInfo($data);
        $this->renderCollectorSummary($data);
        $this->renderException($data);

        return ExitCode::OK;
    }

    private function renderRequestInfo(array $data): void
    {
        foreach (['request' => 'web', 'web' => 'web', 'command' => 'console'] as $key => $type) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $info = $data[$key];
                Console::stdout(sprintf(
                    "Type: %s | Method: %s | URL: %s | Status: %s\n",
                    $type,
                    (string) ($info['method'] ?? '—'),
                    (string) ($info['url'] ?? '—'),
                    (string) ($info['responseStatusCode'] ?? '—'),
                ));
                return;
            }
        }
    }

    private function renderCollectorSummary(array $data): void
    {
        $parts = [];

        foreach ([
            'logger' => 'logs',
            'event' => 'events',
            'db' => 'queries',
            'cache' => 'cache ops',
        ] as $key => $label) {
            $total = (int) ($data[$key]['total'] ?? 0);
            if ($total > 0) {
                $parts[] = sprintf('%s: %d', $label, $total);
            }
        }

        if ($parts !== []) {
            Console::stdout(Console::ansiFormat("\nCollectors: ", [Console::BOLD]));
            Console::stdout(implode(', ', $parts) . "\n");
        }
    }

    private function renderException(array $data): void
    {
        if (!isset($data['exception']) || !is_array($data['exception']) || !isset($data['exception']['class'])) {
            return;
        }

        $exception = $data['exception'];
        Console::stderr(Console::ansiFormat(
            sprintf(
                "\nException: %s: %s\n",
                (string) ($exception['class'] ?? 'Unknown'),
                (string) ($exception['message'] ?? ''),
            ),
            [Console::FG_RED],
        ));

        if (isset($exception['file'])) {
            Console::stderr(sprintf("  at %s:%s\n", (string) $exception['file'], (string) ($exception['line'] ?? '?')));
        }
    }

    private function outputJson(mixed $data): void
    {
        Console::stdout(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}

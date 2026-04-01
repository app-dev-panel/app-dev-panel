<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * Watch debug entries in real-time.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugTailCommand from libs/Cli.
 */
final class DebugTailController extends Controller
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
     * Watch for new debug entries in real-time.
     *
     * @param int $interval Poll interval in seconds.
     * @param bool $json Output raw JSON.
     */
    public function actionIndex(int $interval = 1, bool $json = false): int
    {
        Console::stdout(Console::ansiFormat("Watching debug entries (Ctrl+C to stop)\n", [Console::BOLD]));
        Console::stdout(sprintf("Poll interval: %ds\n\n", max(1, $interval)));

        $knownIds = $this->getEntryIds();

        while (true) {
            sleep(max(1, $interval));

            $currentIds = $this->getEntryIds();
            $newIds = array_diff($currentIds, $knownIds);

            if ($newIds === []) {
                continue;
            }

            foreach ($newIds as $id) {
                $this->renderEntry($id, $json);
            }

            $knownIds = $currentIds;
        }
    }

    /** @return list<string> */
    private function getEntryIds(): array
    {
        $entries = $this->collectorRepository->getSummary();
        $ids = [];
        foreach ($entries as $entry) {
            if (is_array($entry) && isset($entry['id']) && is_string($entry['id'])) {
                $ids[] = $entry['id'];
            }
        }
        return $ids;
    }

    private function renderEntry(string $id, bool $json): void
    {
        $summary = $this->collectorRepository->getSummary($id);

        if ($json) {
            Console::stdout(json_encode($summary, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . "\n");
            return;
        }

        $method = '—';
        $url = '—';
        $status = '—';
        $time = date('H:i:s');

        foreach (['request', 'web', 'command'] as $summaryKey) {
            if (isset($summary[$summaryKey]) && is_array($summary[$summaryKey])) {
                $method = (string) ($summary[$summaryKey]['method'] ?? $method);
                $url = (string) ($summary[$summaryKey]['url'] ?? $url);
                $status = (string) ($summary[$summaryKey]['responseStatusCode'] ?? $status);
                break;
            }
        }

        $hasException = isset($summary['exception']['class']);
        $statusColor = match (true) {
            $hasException, str_starts_with($status, '4'), str_starts_with($status, '5') => Console::FG_RED,
            str_starts_with($status, '2') => Console::FG_GREEN,
            str_starts_with($status, '3') => Console::FG_YELLOW,
            default => Console::FG_GREY,
        };

        Console::stdout(sprintf("[%s] %s %s %s\n", $time, Console::ansiFormat($status, [$statusColor]), $method, $url));
    }
}

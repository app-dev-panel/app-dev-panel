<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Controller;

use AppDevPanel\Api\Debug\Repository\CollectorRepositoryInterface;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;

/**
 * View dumped objects for a debug entry.
 *
 * Yii 2 equivalent of the Symfony Console-based DebugDumpCommand from libs/Cli.
 */
final class DebugDumpController extends Controller
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
     * View all dumped objects for an entry.
     *
     * @param string $id Debug entry ID.
     * @param string|null $collector Filter by collector class.
     * @param bool $json Output raw JSON.
     */
    public function actionIndex(string $id, ?string $collector = null, bool $json = false): int
    {
        try {
            $data = $this->collectorRepository->getDumpObject($id);
        } catch (\Throwable $e) {
            Console::stderr(Console::ansiFormat($e->getMessage() . "\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($collector !== null) {
            if (!array_key_exists($collector, $data)) {
                Console::stderr(Console::ansiFormat(
                    sprintf("Collector \"%s\" not found in dump for entry \"%s\".\n", $collector, $id),
                    [Console::FG_RED],
                ));
                Console::stderr("Available collectors:\n");
                foreach (array_keys($data) as $key) {
                    Console::stderr(sprintf("  - %s\n", (string) $key));
                }
                return ExitCode::UNSPECIFIED_ERROR;
            }
            $data = [$collector => $data[$collector]];
        }

        if (!$json) {
            Console::stdout(Console::ansiFormat(sprintf("Object Dump: %s\n", $id), [Console::BOLD]));
            Console::stdout(str_repeat('=', 60) . "\n");
        }

        if ($data === []) {
            Console::stdout(Console::ansiFormat("No dumped objects found.\n", [Console::FG_YELLOW]));
            return ExitCode::OK;
        }

        $this->outputJson($data);
        return ExitCode::OK;
    }

    /**
     * View a specific object.
     *
     * @param string $id Debug entry ID.
     * @param string $objectId Object ID.
     * @param bool $json Output raw JSON.
     */
    public function actionObject(string $id, string $objectId, bool $json = false): int
    {
        try {
            $data = $this->collectorRepository->getObject($id, $objectId);
        } catch (\Throwable $e) {
            Console::stderr(Console::ansiFormat($e->getMessage() . "\n", [Console::FG_RED]));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        if ($data === null) {
            Console::stderr(Console::ansiFormat(
                sprintf("Object \"%s\" not found in entry \"%s\".\n", $objectId, $id),
                [Console::FG_RED],
            ));
            return ExitCode::UNSPECIFIED_ERROR;
        }

        $result = [
            'class' => $data[0],
            'value' => $data[1],
        ];

        if (!$json) {
            Console::stdout(Console::ansiFormat(sprintf("Object: %s\n", (string) $data[0]), [Console::BOLD]));
            Console::stdout(str_repeat('=', 60) . "\n");
        }

        $this->outputJson($result);
        return ExitCode::OK;
    }

    private function outputJson(mixed $data): void
    {
        Console::stdout(json_encode($data, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    }
}

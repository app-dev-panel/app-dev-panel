<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Debug;

use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Collector\LogCollector;
use AppDevPanel\Kernel\Collector\Web\RequestCollector;
use AppDevPanel\Kernel\Storage\StorageInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

final class AnalyzeExceptionTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly StorageInterface $storage,
    ) {}

    public function getName(): string
    {
        return 'analyze_exception';
    }

    public function getDescription(): string
    {
        return 'Get exception details with full stack trace, related request info, and log messages from the same debug entry. Automatically finds the latest entry with an exception if no ID is given.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'id' => [
                    'type' => 'string',
                    'description' => 'Debug entry ID. If omitted, finds the most recent entry containing an exception.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $id = $arguments['id'] ?? null;

        if ($id === null || $id === '') {
            $id = $this->findLatestExceptionEntry();

            if ($id === null) {
                return self::text('No debug entries with exceptions found.');
            }
        }

        $allData = $this->storage->read(StorageInterface::TYPE_DATA, $id);

        if (!array_key_exists($id, $allData)) {
            return self::error(sprintf('Debug entry "%s" not found.', $id));
        }

        $data = $allData[$id];
        $exceptionData = $data[ExceptionCollector::class] ?? null;

        if ($exceptionData === null || $exceptionData === []) {
            return self::text(sprintf('No exception found in debug entry "%s".', $id));
        }

        $output = $this->formatException($id, $exceptionData, $data);

        return self::text($output);
    }

    private function findLatestExceptionEntry(): ?string
    {
        $summaries = $this->storage->read(StorageInterface::TYPE_SUMMARY);
        $entries = array_reverse($summaries, true);

        foreach ($entries as $id => $summary) {
            if (array_key_exists('exception', $summary) && $summary['exception'] !== []) {
                return $id;
            }
        }

        return null;
    }

    private function formatException(string $entryId, array $exceptionData, array $allCollectorData): string
    {
        $sections = ["# Exception in entry: {$entryId}\n"];

        foreach ($exceptionData as $i => $exception) {
            $prefix = $i === 0 ? '## Exception' : '## Previous Exception';
            $sections[] = $prefix;
            $sections[] = sprintf('- **Class**: %s', $exception['class'] ?? 'unknown');
            $sections[] = sprintf('- **Message**: %s', $exception['message'] ?? '');
            $sections[] = sprintf('- **File**: %s:%d', $exception['file'] ?? '?', $exception['line'] ?? 0);
            $sections[] = sprintf('- **Code**: %s', $exception['code'] ?? 0);

            if (($exception['traceAsString'] ?? '') !== '') {
                $trace = $exception['traceAsString'];
                if (mb_strlen($trace) > 3000) {
                    $trace = mb_substr($trace, 0, 3000) . "\n... (truncated)";
                }
                $sections[] = "\n### Stack Trace\n```\n{$trace}\n```";
            }
        }

        $requestData = $allCollectorData[RequestCollector::class] ?? null;
        if ($requestData !== null && $requestData !== []) {
            $sections[] = "\n## Request Context";
            $sections[] = json_encode(
                $requestData,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT,
            );
        }

        $logData = $allCollectorData[LogCollector::class] ?? null;
        if (is_array($logData) && $logData !== []) {
            $sections[] = "\n## Related Log Messages";
            $count = 0;
            foreach ($logData as $log) {
                if ($count >= 20) {
                    $sections[] = sprintf('... and %d more', count($logData) - 20);
                    break;
                }
                $level = strtoupper($log['level'] ?? '?');
                $sections[] = sprintf('- [%s] %s', $level, $log['message'] ?? '');
                $count++;
            }
        }

        return implode("\n", $sections);
    }
}

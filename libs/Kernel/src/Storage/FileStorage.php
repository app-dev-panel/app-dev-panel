<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Dumper;
use Yiisoft\Files\FileHelper;
use Yiisoft\Json\Json;

final class FileStorage implements StorageInterface
{
    public const int DEFAULT_HISTORY_SIZE = 50;
    public const int DEFAULT_COMPRESSION_LEVEL = 1;

    /**
     * @var CollectorInterface[]
     */
    private array $collectors = [];

    private int $historySize = self::DEFAULT_HISTORY_SIZE;

    public function __construct(
        private readonly string $path,
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly array $excludedClasses = [],
        private readonly int $compressionLevel = self::DEFAULT_COMPRESSION_LEVEL,
    ) {}

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[$collector->getId()] = $collector;
    }

    public function setHistorySize(int $historySize): void
    {
        $this->historySize = $historySize;
    }

    public function read(string $type, ?string $id = null): array
    {
        clearstatcache();
        $data = [];

        $gzPattern = sprintf('%s/**/%s/%s.json.gz', $this->path, $id ?? '**', $type);
        $jsonPattern = sprintf('%s/**/%s/%s.json', $this->path, $id ?? '**', $type);

        $gzFiles = glob($gzPattern, GLOB_NOSORT) ?: [];
        $jsonFiles = glob($jsonPattern, GLOB_NOSORT) ?: [];

        // Index gz files by entry directory to skip legacy duplicates
        $gzDirs = [];
        foreach ($gzFiles as $file) {
            $gzDirs[dirname($file)] = true;
        }

        // Merge: prefer .json.gz, fall back to .json for legacy entries
        $dataFiles = $gzFiles;
        foreach ($jsonFiles as $file) {
            if (array_key_exists(dirname($file), $gzDirs)) {
                continue;
            }

            $dataFiles[] = $file;
        }

        uasort($dataFiles, static fn($a, $b) => filemtime($a) <=> filemtime($b));

        foreach ($dataFiles as $file) {
            $dir = dirname($file);
            $entryId = substr($dir, strlen(dirname($file, 2)) + 1);
            $raw = file_get_contents($file);
            if (str_ends_with($file, '.gz')) {
                $raw = gzdecode($raw);
            }
            $data[$entryId] = Json::decode($raw);
        }

        return $data;
    }

    public function write(string $id, array $summary, array $data, array $objects): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';

        FileHelper::ensureDirectory($basePath);

        $this->writeCompressed($basePath . self::TYPE_SUMMARY, Json::encode($summary));
        $this->writeCompressed($basePath . self::TYPE_DATA, Dumper::create($data)->asJson(30));
        $this->writeCompressed($basePath . self::TYPE_OBJECTS, Dumper::create($objects)->asJsonObjectsMap(30));
    }

    public function flush(): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $this->idGenerator->getId() . '/';

        try {
            FileHelper::ensureDirectory($basePath);

            $dumper = Dumper::create($this->getData(), $this->excludedClasses);
            $this->writeCompressed($basePath . self::TYPE_DATA, $dumper->asJson(30));
            $this->writeCompressed($basePath . self::TYPE_OBJECTS, $dumper->asJsonObjectsMap(30));

            $summaryData = Dumper::create($this->collectSummaryData())->asJson();
            $this->writeCompressed($basePath . self::TYPE_SUMMARY, $summaryData);
        } finally {
            $this->collectors = [];
            new FileStorageGarbageCollector($this->path, $this->historySize)->run();
        }
    }

    public function getData(): array
    {
        return array_map(static fn(CollectorInterface $collector) => $collector->getCollected(), $this->collectors);
    }

    public function clear(): void
    {
        FileHelper::removeDirectory($this->path);
    }

    /**
     * Collects summary data of current request.
     */
    private function collectSummaryData(): array
    {
        $summaryData = [
            'id' => $this->idGenerator->getId(),
            'collectors' => array_map(static fn(CollectorInterface $collector) => [
                'id' => $collector->getId(),
                'name' => $collector->getName(),
            ], array_values($this->collectors)),
        ];

        foreach ($this->collectors as $collector) {
            if (!$collector instanceof SummaryCollectorInterface) {
                continue;
            }

            $summaryData = [...$summaryData, ...$collector->getSummary()];
        }

        return $summaryData;
    }

    /**
     * Compresses content with gzip and writes to a .json.gz file with an exclusive lock.
     *
     * @throws \RuntimeException if the file cannot be written.
     */
    private function writeCompressed(string $baseFilePath, string $content): void
    {
        $filePath = $baseFilePath . '.json.gz';
        $compressed = gzencode($content, $this->compressionLevel);
        $result = file_put_contents($filePath, $compressed, LOCK_EX);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to write file "%s".', $filePath));
        }
    }
}

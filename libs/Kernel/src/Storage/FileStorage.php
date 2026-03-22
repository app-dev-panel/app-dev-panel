<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Dumper;
use Yiisoft\Files\FileHelper;
use Yiisoft\Json\Json;

use function array_slice;
use function count;
use function dirname;
use function filemtime;
use function glob;
use function strlen;
use function substr;
use function uasort;

final class FileStorage implements StorageInterface
{
    public const int DEFAULT_HISTORY_SIZE = 50;

    /**
     * @var CollectorInterface[]
     */
    private array $collectors = [];

    private int $historySize = self::DEFAULT_HISTORY_SIZE;

    public function __construct(
        private readonly string $path,
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly array $excludedClasses = [],
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
        $pattern = sprintf('%s/**/%s/%s.json', $this->path, $id ?? '**', $type);
        $dataFiles = glob($pattern, GLOB_NOSORT);
        uasort($dataFiles, static fn($a, $b) => filemtime($a) <=> filemtime($b));

        foreach ($dataFiles as $file) {
            $dir = dirname($file);
            $entryId = substr($dir, strlen(dirname($file, 2)) + 1);
            $data[$entryId] = Json::decode(file_get_contents($file));
        }

        return $data;
    }

    public function write(string $id, array $summary, array $data, array $objects): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $id . '/';

        FileHelper::ensureDirectory($basePath);

        $this->writeFileExclusive($basePath . self::TYPE_SUMMARY . '.json', Json::encode($summary));
        $this->writeFileExclusive($basePath . self::TYPE_DATA . '.json', Dumper::create($data)->asJson(30));
        $this->writeFileExclusive(
            $basePath . self::TYPE_OBJECTS . '.json',
            Dumper::create($objects)->asJsonObjectsMap(30),
        );
    }

    public function flush(): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $this->idGenerator->getId() . '/';

        try {
            FileHelper::ensureDirectory($basePath);

            $dumper = Dumper::create($this->getData(), $this->excludedClasses);
            $this->writeFileExclusive($basePath . self::TYPE_DATA . '.json', $dumper->asJson(30));
            $this->writeFileExclusive($basePath . self::TYPE_OBJECTS . '.json', $dumper->asJsonObjectsMap(30));

            $summaryData = Dumper::create($this->collectSummaryData())->asJson();
            $this->writeFileExclusive($basePath . self::TYPE_SUMMARY . '.json', $summaryData);
        } finally {
            $this->collectors = [];
            $this->gc();
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
     * Writes content to a file with an exclusive lock to prevent race conditions.
     *
     * @throws \RuntimeException if the file cannot be written.
     */
    private function writeFileExclusive(string $filePath, string $content): void
    {
        $result = file_put_contents($filePath, $content, LOCK_EX);
        if ($result === false) {
            throw new \RuntimeException(sprintf('Failed to write file "%s".', $filePath));
        }
    }

    /**
     * Removes obsolete data files
     */
    private function gc(): void
    {
        $lockFile = $this->path . '/.gc.lock';
        $lockHandle = @fopen($lockFile, 'c');
        if ($lockHandle === false) {
            return;
        }

        if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
            fclose($lockHandle);
            return;
        }

        try {
            $summaryFiles = glob($this->path . '/**/**/summary.json', GLOB_NOSORT);
            if ($summaryFiles === false || $summaryFiles === [] || count($summaryFiles) <= $this->historySize) {
                return;
            }

            uasort($summaryFiles, static fn($a, $b) => filemtime($b) <=> filemtime($a));
            $excessFiles = array_slice($summaryFiles, $this->historySize);
            foreach ($excessFiles as $file) {
                if (!file_exists($file)) {
                    continue;
                }
                $path1 = dirname($file);
                $path2 = dirname($file, 2);
                $path3 = dirname($file, 3);
                $resource = substr($path1, strlen($path3));

                FileHelper::removeDirectory($this->path . $resource);

                // Clean empty group directories
                $group = substr($path2, strlen($path3));
                if (FileHelper::isEmptyDirectory($this->path . $group)) {
                    FileHelper::removeDirectory($this->path . $group);
                }
            }
        } finally {
            flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
            @unlink($lockFile);
        }
    }
}

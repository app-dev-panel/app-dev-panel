<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Dumper;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

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
    /**
     * @var CollectorInterface[]
     */
    private array $collectors = [];

    private int $historySize = 50;

    public function __construct(
        private readonly string $path,
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly array $excludedClasses = [],
    ) {}

    public function addCollector(CollectorInterface $collector): void
    {
        $this->collectors[$collector->getName()] = $collector;
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
            $data[$entryId] = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        }

        return $data;
    }

    public function flush(): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $this->idGenerator->getId() . '/';

        try {
            self::ensureDirectory($basePath);

            $dumper = Dumper::create($this->getData(), $this->excludedClasses);
            $result = file_put_contents($basePath . self::TYPE_DATA . '.json', $dumper->asJson(30));
            if ($result === false) {
                throw new RuntimeException(sprintf(
                    'Failed to write file "%s".',
                    $basePath . self::TYPE_DATA . '.json',
                ));
            }
            $result = file_put_contents($basePath . self::TYPE_OBJECTS . '.json', $dumper->asJsonObjectsMap(30));
            if ($result === false) {
                throw new RuntimeException(sprintf(
                    'Failed to write file "%s".',
                    $basePath . self::TYPE_OBJECTS . '.json',
                ));
            }

            $summaryData = Dumper::create($this->collectSummaryData())->asJson();
            $result = file_put_contents($basePath . self::TYPE_SUMMARY . '.json', $summaryData);
            if ($result === false) {
                throw new RuntimeException(sprintf(
                    'Failed to write file "%s".',
                    $basePath . self::TYPE_SUMMARY . '.json',
                ));
            }
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
        self::removeDirectory($this->path);
    }

    /**
     * Collects summary data of current request.
     */
    private function collectSummaryData(): array
    {
        $summaryData = [
            'id' => $this->idGenerator->getId(),
            'collectors' => array_keys($this->collectors),
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
     * Removes obsolete data files
     */
    private function gc(): void
    {
        $summaryFiles = glob($this->path . '/**/**/summary.json', GLOB_NOSORT);
        if (empty($summaryFiles) || count($summaryFiles) <= $this->historySize) {
            return;
        }

        uasort($summaryFiles, static fn($a, $b) => filemtime($b) <=> filemtime($a));
        $excessFiles = array_slice($summaryFiles, $this->historySize);
        foreach ($excessFiles as $file) {
            $path1 = dirname($file);
            $path2 = dirname($file, 2);
            $path3 = dirname($file, 3);
            $resource = substr($path1, strlen($path3));

            self::removeDirectory($this->path . $resource);

            // Clean empty group directories
            $group = substr($path2, strlen($path3));
            if (self::isEmptyDirectory($this->path . $group)) {
                self::removeDirectory($this->path . $group);
            }
        }
    }

    private static function ensureDirectory(string $path): void
    {
        if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
            throw new RuntimeException(sprintf('Directory "%s" could not be created.', $path));
        }
    }

    private static function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }

        rmdir($path);
    }

    private static function isEmptyDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return false;
        }

        return !new FilesystemIterator($path)->valid();
    }
}

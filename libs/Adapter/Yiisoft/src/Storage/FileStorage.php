<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Storage;

use Yiisoft\Files\FileHelper;
use Yiisoft\Json\Json;
use Yiisoft\Yii\Debug\Collector\CollectorInterface;
use Yiisoft\Yii\Debug\Collector\SummaryCollectorInterface;
use Yiisoft\Yii\Debug\DebuggerIdGenerator;
use Yiisoft\Yii\Debug\Dumper;

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
        private readonly array $excludedClasses = []
    ) {
    }

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
        $pattern = sprintf(
            '%s/**/%s/%s.json',
            $this->path,
            $id ?? '**',
            $type,
        );
        $dataFiles = glob($pattern, GLOB_NOSORT);
        uasort($dataFiles, static fn ($a, $b) => filemtime($a) <=> filemtime($b));

        foreach ($dataFiles as $file) {
            $dir = dirname($file);
            $id = substr($dir, strlen(dirname($file, 2)) + 1);
            $data[$id] = Json::decode(file_get_contents($file));
        }

        return $data;
    }

    public function flush(): void
    {
        $basePath = $this->path . '/' . date('Y-m-d') . '/' . $this->idGenerator->getId() . '/';

        try {
            FileHelper::ensureDirectory($basePath);

            $dumper = Dumper::create($this->getData(), $this->excludedClasses);
            file_put_contents($basePath . self::TYPE_DATA . '.json', $dumper->asJson(30));
            file_put_contents($basePath . self::TYPE_OBJECTS . '.json', $dumper->asJsonObjectsMap(30));

            $summaryData = Dumper::create($this->collectSummaryData())->asJson();
            file_put_contents($basePath . self::TYPE_SUMMARY . '.json', $summaryData);
        } finally {
            $this->collectors = [];
            $this->gc();
        }
    }

    public function getData(): array
    {
        return array_map(static fn (CollectorInterface $collector) => $collector->getCollected(), $this->collectors);
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
            'collectors' => array_keys($this->collectors),
        ];

        foreach ($this->collectors as $collector) {
            if ($collector instanceof SummaryCollectorInterface) {
                $summaryData = [...$summaryData, ...$collector->getSummary()];
            }
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

        uasort($summaryFiles, static fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $excessFiles = array_slice($summaryFiles, $this->historySize);
        foreach ($excessFiles as $file) {
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
    }
}

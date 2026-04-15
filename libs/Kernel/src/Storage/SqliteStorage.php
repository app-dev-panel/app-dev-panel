<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Storage;

use AppDevPanel\Kernel\Collector\CollectorInterface;
use AppDevPanel\Kernel\Collector\SummaryCollectorInterface;
use AppDevPanel\Kernel\DebuggerIdGenerator;
use AppDevPanel\Kernel\Dumper;
use AppDevPanel\Kernel\Helper\Json;

final class SqliteStorage implements StorageInterface
{
    public const int DEFAULT_HISTORY_SIZE = 50;

    /**
     * @var CollectorInterface[]
     */
    private array $collectors = [];

    private int $historySize = self::DEFAULT_HISTORY_SIZE;

    private \PDO $pdo;

    public function __construct(
        private readonly string $path,
        private readonly DebuggerIdGenerator $idGenerator,
        private readonly array $excludedClasses = [],
    ) {
        $this->pdo = $this->createConnection();
        $this->ensureSchema();
    }

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
        if ($id !== null) {
            return $this->readEntry($type, $id);
        }

        return $this->readAll($type);
    }

    public function write(string $id, array $summary, array $data, array $objects): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT OR REPLACE INTO entries (id, summary, data, objects, created_at) VALUES (:id, :summary, :data, :objects, :created_at)',
        );

        $stmt->execute([
            ':id' => $id,
            ':summary' => Json::encode($summary),
            ':data' => Dumper::create($data)->asJson(30),
            ':objects' => Dumper::create($objects)->asJsonObjectsMap(30),
            ':created_at' => time(),
        ]);
    }

    public function flush(): void
    {
        try {
            $dumper = Dumper::create($this->getData(), $this->excludedClasses);
            $dataJson = $dumper->asJson(30);
            $objectsJson = $dumper->asJsonObjectsMap(30);
            $summaryJson = Dumper::create($this->collectSummaryData())->asJson();

            $stmt = $this->pdo->prepare(
                'INSERT OR REPLACE INTO entries (id, summary, data, objects, created_at) VALUES (:id, :summary, :data, :objects, :created_at)',
            );
            $stmt->execute([
                ':id' => $this->idGenerator->getId(),
                ':summary' => $summaryJson,
                ':data' => $dataJson,
                ':objects' => $objectsJson,
                ':created_at' => time(),
            ]);
        } finally {
            $this->collectors = [];
            $this->garbageCollect();
        }
    }

    public function getData(): array
    {
        return array_map(static fn(CollectorInterface $collector) => $collector->getCollected(), $this->collectors);
    }

    public function clear(): void
    {
        $this->pdo->exec('DELETE FROM entries');
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

    private function readEntry(string $type, string $id): array
    {
        $column = $this->resolveColumn($type);

        $stmt = $this->pdo->prepare("SELECT {$column} FROM entries WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return [];
        }

        return [$id => Json::decode($row[$column])];
    }

    private function readAll(string $type): array
    {
        $column = $this->resolveColumn($type);

        $stmt = $this->pdo->query("SELECT id, {$column} FROM entries ORDER BY created_at ASC");
        $data = [];

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $data[$row['id']] = Json::decode($row[$column]);
        }

        return $data;
    }

    /**
     * Map the public storage type to an actual SQL column. Only the known
     * type constants are accepted — unknown input is rejected to prevent
     * column-name SQL injection through the `{$column}` interpolation below.
     */
    private function resolveColumn(string $type): string
    {
        return match ($type) {
            self::TYPE_SUMMARY => 'summary',
            self::TYPE_DATA => 'data',
            self::TYPE_OBJECTS => 'objects',
            default => throw new \InvalidArgumentException(sprintf('Unknown storage type "%s".', $type)),
        };
    }

    private function garbageCollect(): void
    {
        $stmt = $this->pdo->query('SELECT COUNT(*) FROM entries');
        $count = (int) $stmt->fetchColumn();

        if ($count <= $this->historySize) {
            return;
        }

        $excess = $count - $this->historySize;
        $this->pdo->exec(
            "DELETE FROM entries WHERE id IN (SELECT id FROM entries ORDER BY created_at ASC LIMIT {$excess})",
        );
    }

    private function createConnection(): \PDO
    {
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0o777, true);
        }

        $pdo = new \PDO('sqlite:' . $this->path, options: [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        ]);

        $pdo->exec('PRAGMA journal_mode=WAL');
        $pdo->exec('PRAGMA synchronous=NORMAL');

        return $pdo;
    }

    private function ensureSchema(): void
    {
        $this->pdo->exec('CREATE TABLE IF NOT EXISTS entries (
                id TEXT PRIMARY KEY,
                summary TEXT NOT NULL,
                data TEXT NOT NULL,
                objects TEXT NOT NULL,
                created_at INTEGER NOT NULL
            )');

        $this->pdo->exec('CREATE INDEX IF NOT EXISTS idx_entries_created_at ON entries (created_at)');
    }
}

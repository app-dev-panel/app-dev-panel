<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Repository;

use Yiisoft\Yii\Debug\Api\Debug\Exception\NotFoundException;
use Yiisoft\Yii\Debug\Storage\StorageInterface;

final class CollectorRepository implements CollectorRepositoryInterface
{
    public function __construct(private StorageInterface $storage)
    {
    }

    public function getSummary(?string $id = null): array
    {
        $data = $this->loadData(StorageInterface::TYPE_SUMMARY, $id);
        if ($id !== null) {
            return $data;
        }

        return array_values(array_reverse($data));
    }

    public function getDetail(string $id): array
    {
        return $this->loadData(StorageInterface::TYPE_DATA, $id);
    }

    public function getDumpObject(string $id): array
    {
        return $this->loadData(StorageInterface::TYPE_OBJECTS, $id);
    }

    public function getObject(string $id, string $objectId): array|null
    {
        $dump = $this->loadData(StorageInterface::TYPE_OBJECTS, $id);

        foreach ($dump as $name => $value) {
            if (($pos = strrpos((string)$name, "#$objectId")) !== false) {
                return [substr($name, 0, $pos), $value];
            }
        }

        return null;
    }

    /**
     * @throws NotFoundException
     */
    private function loadData(string $fileType, ?string $id = null): array
    {
        $data = $this->storage->read($fileType, $id);
        if (!empty($id)) {
            if (!isset($data[$id])) {
                throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
            }

            return $data[$id];
        }

        return $data;
    }
}

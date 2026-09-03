<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Debug\Repository;

use AppDevPanel\Api\Debug\Exception\NotFoundException;
use AppDevPanel\Api\Security\DebugIdValidator;
use AppDevPanel\Kernel\Storage\StorageInterface;

final class CollectorRepository implements CollectorRepositoryInterface
{
    public function __construct(
        private StorageInterface $storage,
    ) {}

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

    public function getObject(string $id, string $objectId): ?array
    {
        $dump = $this->loadData(StorageInterface::TYPE_OBJECTS, $id);
        $suffix = "#{$objectId}";

        foreach ($dump as $name => $value) {
            $name = (string) $name;
            if (str_ends_with($name, $suffix)) {
                return [substr($name, 0, -strlen($suffix)), $value];
            }
        }

        return null;
    }

    /**
     * @throws NotFoundException
     * @throws \AppDevPanel\Api\Debug\Exception\BadRequestException when `$id` is not a valid entry id
     */
    private function loadData(string $fileType, ?string $id = null): array
    {
        if ($id !== null && $id !== '') {
            // The id becomes a path segment inside the storage; refuse anything else early.
            DebugIdValidator::assertValid($id);
        }

        $data = $this->storage->read($fileType, $id);
        if ($id !== null && $id !== '') {
            if (!array_key_exists($id, $data)) {
                throw new NotFoundException(sprintf('Unable to find debug data ID with "%s"', $id));
            }

            return $data[$id];
        }

        return $data;
    }
}

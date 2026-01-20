<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Debug\Repository;

interface CollectorRepositoryInterface
{
    public function getSummary(?string $id = null): array;

    public function getDetail(string $id): array;

    public function getDumpObject(string $id): array;

    /**
     * @return array{string, mixed}|null Returns a list with object class, and it's value or null
     */
    public function getObject(string $id, string $objectId): array|null;
}

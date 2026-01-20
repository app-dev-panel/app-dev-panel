<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Api\Inspector\Controller;

use Psr\Http\Message\ResponseInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;

final class OpcacheController
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
    ) {
    }

    public function index(): ResponseInterface
    {
        if (!\function_exists('opcache_get_status') || ($status = \opcache_get_status(true)) === false) {
            return $this->responseFactory->createResponse([
                'message' => 'OPcache is not installed or configured',
            ], Status::UNPROCESSABLE_ENTITY);
        }

        return $this->responseFactory->createResponse([
            'status' => $status,
            'configuration' => \opcache_get_configuration(),
        ]);
    }
}

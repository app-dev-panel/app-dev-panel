<?php

declare(strict_types=1);

namespace App\Web\Api;

use OpenApi\Attributes as OA;
use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class ErrorAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    #[OA\Get(
        path: '/api/error',
        summary: 'Trigger a demo exception',
        tags: ['General'],
        responses: [
            new OA\Response(response: 500, description: 'Demo exception'),
        ],
    )]
    public function __invoke(): never
    {
        $this->logger->warning('About to trigger a demo exception');

        throw new RuntimeException('This is a demo exception for ADP debugging');
    }
}

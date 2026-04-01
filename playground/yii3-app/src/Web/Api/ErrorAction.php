<?php

declare(strict_types=1);

namespace App\Web\Api;

use Psr\Log\LoggerInterface;
use RuntimeException;

final readonly class ErrorAction
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function __invoke(): never
    {
        $this->logger->warning('About to trigger a demo exception');

        throw new RuntimeException('This is a demo exception for ADP debugging');
    }
}

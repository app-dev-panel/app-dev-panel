<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Doctrine\DBAL\Driver as DriverInterface;
use Doctrine\DBAL\Driver\Middleware;

/**
 * Doctrine DBAL Middleware that feeds query data to DatabaseCollector.
 *
 * Wraps the DBAL Driver to intercept all query execution and transaction operations.
 * Registered in the Symfony DI container via CollectorProxyCompilerPass.
 */
final class DoctrineDbalMiddleware implements Middleware
{
    public function __construct(
        private readonly DatabaseCollector $collector,
    ) {}

    public function wrap(DriverInterface $driver): DriverInterface
    {
        return new DoctrineDbalDriver($driver, $this->collector);
    }
}

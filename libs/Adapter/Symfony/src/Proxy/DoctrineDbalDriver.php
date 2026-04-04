<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Proxy;

use AppDevPanel\Kernel\Collector\DatabaseCollector;
use Doctrine\DBAL\Connection\StaticServerVersionProvider;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * DBAL driver wrapper that intercepts connect() to return a profiling connection.
 */
final class DoctrineDbalDriver extends AbstractDriverMiddleware
{
    public function __construct(
        Driver $driver,
        private readonly DatabaseCollector $collector,
    ) {
        parent::__construct($driver);
    }

    public function connect(#[\SensitiveParameter] array $params): DriverConnection
    {
        return new DoctrineDbalConnection(parent::connect($params), $this->collector);
    }
}

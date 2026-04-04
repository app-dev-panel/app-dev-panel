<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

final readonly class DatabaseAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private ConnectionInterface $connection,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        // Execute real SQL queries via yiisoft/db — the ConnectionInterfaceProxy
        // intercepts these calls and feeds query data to DatabaseCollector.
        $this->connection
            ->createCommand('CREATE TABLE IF NOT EXISTS test_users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)')
            ->execute();

        $this->connection
            ->createCommand('INSERT OR REPLACE INTO test_users (id, name, email) VALUES (:id, :name, :email)', [
                'id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ])
            ->execute();

        $result = $this->connection->createCommand('SELECT * FROM test_users WHERE id = :id', ['id' => 1])->queryOne();

        return $this->responseFactory->createResponse([
            'fixture' => 'database:basic',
            'status' => 'ok',
            'user' => $result,
        ]);
    }
}

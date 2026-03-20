<?php

declare(strict_types=1);

namespace App\Web\TestFixtures;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\DataResponse\DataResponseFactoryInterface;

final readonly class RouterAction implements RequestHandlerInterface
{
    public function __construct(
        private DataResponseFactoryInterface $responseFactory,
        private RouterCollector $routerCollector,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->routerCollector->collectMatchedRoute([
            'name' => 'test-router',
            'pattern' => '/test/fixtures/router',
            'arguments' => [],
            'uri' => '/test/fixtures/router',
            'host' => null,
            'action' => self::class,
            'middlewares' => [],
            'matchTime' => 0.123,
        ]);
        $this->routerCollector->collectRoutes(routes: [
            ['name' => 'home', 'pattern' => '/', 'methods' => ['GET']],
            ['name' => 'test-router', 'pattern' => '/test/fixtures/router', 'methods' => ['GET']],
            ['name' => 'test-logs', 'pattern' => '/test/fixtures/logs', 'methods' => ['GET']],
        ]);

        return $this->responseFactory->createResponse(['fixture' => 'router:basic', 'status' => 'ok']);
    }
}

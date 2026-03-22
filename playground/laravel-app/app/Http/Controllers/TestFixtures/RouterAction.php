<?php

declare(strict_types=1);

namespace App\Http\Controllers\TestFixtures;

use AppDevPanel\Kernel\Collector\RouterCollector;
use Illuminate\Http\JsonResponse;

final readonly class RouterAction
{
    public function __construct(
        private RouterCollector $routerCollector,
    ) {}

    public function __invoke(): JsonResponse
    {
        $this->routerCollector->collectMatchedRoute([
            'name' => 'test_router',
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
            ['name' => 'test_router', 'pattern' => '/test/fixtures/router', 'methods' => ['GET']],
            ['name' => 'test_logs', 'pattern' => '/test/fixtures/logs', 'methods' => ['GET']],
        ]);

        return new JsonResponse(['fixture' => 'router:basic', 'status' => 'ok']);
    }
}

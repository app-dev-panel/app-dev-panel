<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Tests\Unit;

use AppDevPanel\Api\ApiRoutes;
use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Controller\SettingsController;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Api\Ingestion\Controller\OtlpController;
use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;
use PHPUnit\Framework\TestCase;

final class ApiRoutesTest extends TestCase
{
    public function testDebugRoutesReturnRoutes(): void
    {
        $routes = ApiRoutes::debugRoutes();

        $this->assertNotEmpty($routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);

        $paths = array_map(static fn(Route $r) => $r->pattern, $routes);
        $this->assertContains('/debug/api', $paths);
        $this->assertContains('/debug/api/view/{id}', $paths);
        $this->assertContains('/debug/api/event-stream', $paths);
    }

    public function testIngestionRoutesReturnRoutes(): void
    {
        $routes = ApiRoutes::ingestionRoutes();

        $this->assertNotEmpty($routes);
        $paths = array_map(static fn(Route $r) => $r->pattern, $routes);
        $this->assertContains('/debug/api/ingest', $paths);
        $this->assertContains('/debug/api/ingest/batch', $paths);
        $this->assertContains('/debug/api/ingest/log', $paths);
        $this->assertContains('/debug/api/openapi.json', $paths);
        $this->assertContains('/debug/api/otlp/v1/traces', $paths);
    }

    public function testServiceRoutesReturnRoutes(): void
    {
        $routes = ApiRoutes::serviceRoutes();

        $this->assertNotEmpty($routes);
        $paths = array_map(static fn(Route $r) => $r->pattern, $routes);
        $this->assertContains('/debug/api/services/register', $paths);
        $this->assertContains('/debug/api/services/heartbeat', $paths);
        $this->assertContains('/debug/api/services', $paths);
        $this->assertContains('/debug/api/services/{service}', $paths);
    }

    public function testInspectorRoutesReturnRoutes(): void
    {
        $routes = ApiRoutes::inspectorRoutes();

        $this->assertNotEmpty($routes);
        $paths = array_map(static fn(Route $r) => $r->pattern, $routes);
        $this->assertContains('/inspect/api/routes', $paths);
        $this->assertContains('/inspect/api/config', $paths);
        $this->assertContains('/inspect/api/files', $paths);
        $this->assertContains('/inspect/api/table', $paths);
        $this->assertContains('/inspect/api/git/summary', $paths);
        $this->assertContains('/inspect/api/command', $paths);
        $this->assertContains('/inspect/api/composer', $paths);
        $this->assertContains('/inspect/api/cache', $paths);
        $this->assertContains('/inspect/api/opcache', $paths);
        $this->assertContains('/inspect/api/mcp', $paths);
        $this->assertContains('/inspect/api/mcp/settings', $paths);
        $this->assertContains('/inspect/api/redis/ping', $paths);
        $this->assertContains('/inspect/api/redis/info', $paths);
        $this->assertContains('/inspect/api/redis/keys', $paths);
    }

    public function testAllRoutesHaveNames(): void
    {
        $allRoutes = array_merge(
            ApiRoutes::debugRoutes(),
            ApiRoutes::ingestionRoutes(),
            ApiRoutes::serviceRoutes(),
            ApiRoutes::inspectorRoutes(),
        );

        foreach ($allRoutes as $route) {
            $this->assertNotNull($route->name, "Route {$route->pattern} should have a name");
        }
    }

    public function testRegisterAddsAllRoutes(): void
    {
        $router = new Router();
        ApiRoutes::register($router);

        $expected =
            count(ApiRoutes::debugRoutes())
            + count(ApiRoutes::ingestionRoutes())
            + count(ApiRoutes::serviceRoutes())
            + count(ApiRoutes::inspectorRoutes())
            + count(ApiRoutes::llmRoutes());

        $this->assertCount($expected, $router->getRoutes());
    }

    public function testDebugRoutesUseExpectedControllers(): void
    {
        $allowedControllers = [
            DebugController::class,
            SettingsController::class,
        ];

        foreach (ApiRoutes::debugRoutes() as $route) {
            $this->assertContains(
                $route->handler[0],
                $allowedControllers,
                sprintf('Route %s uses unexpected controller %s', $route->pattern, $route->handler[0]),
            );
        }
    }

    public function testIngestionRoutesUseIngestionControllers(): void
    {
        $allowedControllers = [IngestionController::class, OtlpController::class];
        foreach (ApiRoutes::ingestionRoutes() as $route) {
            $this->assertContains($route->handler[0], $allowedControllers);
        }
    }
}

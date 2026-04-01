<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii2\Tests\Unit\Controller;

use AppDevPanel\Adapter\Yii2\Controller\InspectRoutesController;
use PHPUnit\Framework\TestCase;
use yii\console\Application;
use yii\console\ExitCode;

final class InspectRoutesControllerTest extends TestCase
{
    private string $basePath;

    protected function setUp(): void
    {
        \Yii::$container = new \yii\di\Container();

        $this->basePath = sys_get_temp_dir() . '/adp_inspect_routes_test_' . bin2hex(random_bytes(4));
        mkdir($this->basePath, 0o777, true);

        new Application([
            'id' => 'test',
            'basePath' => $this->basePath,
        ]);
    }

    protected function tearDown(): void
    {
        \Yii::$container = new \yii\di\Container();
        \Yii::$app = null;

        if (is_dir($this->basePath)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->basePath, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST,
            );
            foreach ($items as $item) {
                $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
            }
            rmdir($this->basePath);
        }
    }

    public function testDefaultActionIsList(): void
    {
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app);

        $this->assertSame('list', $controller->defaultAction);
    }

    public function testActionListWithNullRouteCollection(): void
    {
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, null);
        $result = $controller->actionList();

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    public function testActionListWithEmptyRoutes(): void
    {
        $routeCollection = $this->createRouteCollection([]);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListWithRoutes(): void
    {
        $routes = [
            $this->createRoute('home', '/', ['GET']),
            $this->createRoute('api.users', '/api/users', ['GET', 'POST']),
        ];
        $routeCollection = $this->createRouteCollection($routes);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListJsonOutput(): void
    {
        $routes = [
            $this->createRoute('home', '/', ['GET']),
        ];
        $routeCollection = $this->createRouteCollection($routes);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList(json: true);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListJsonOutputWithEmptyRoutes(): void
    {
        $routeCollection = $this->createRouteCollection([]);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList(json: true);

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListWithNonArrayMethods(): void
    {
        $route = $this->createRoute('legacy', '/legacy', 'ANY');
        $routeCollection = $this->createRouteCollection([$route]);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListWithNullFields(): void
    {
        $route = new class {
            public function __debugInfo(): array
            {
                return ['name' => null, 'pattern' => null, 'methods' => null];
            }
        };
        $routeCollection = $this->createRouteCollection([$route]);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListWithMultipleRoutes(): void
    {
        $routes = [
            $this->createRoute('route1', '/path1', ['GET']),
            $this->createRoute('route2', '/path2', ['POST']),
            $this->createRoute('route3', '/path3', ['PUT', 'PATCH']),
        ];
        $routeCollection = $this->createRouteCollection($routes);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->actionList();

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListViaRunAction(): void
    {
        $routeCollection = $this->createRouteCollection([]);
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, $routeCollection);

        $result = $controller->runAction('list');

        $this->assertSame(ExitCode::OK, $result);
    }

    public function testActionListNullRouteCollectionJsonOutput(): void
    {
        $controller = new InspectRoutesController('inspect-routes', \Yii::$app, null);
        $result = $controller->actionList(json: true);

        $this->assertSame(ExitCode::UNSPECIFIED_ERROR, $result);
    }

    /**
     * Creates a mock route object with __debugInfo().
     */
    private function createRoute(string $name, string $pattern, string|array $methods): object
    {
        return new class($name, $pattern, $methods) {
            public function __construct(
                private readonly string $name,
                private readonly string $pattern,
                private readonly string|array $methods,
            ) {}

            public function __debugInfo(): array
            {
                return [
                    'name' => $this->name,
                    'pattern' => $this->pattern,
                    'methods' => $this->methods,
                ];
            }
        };
    }

    /**
     * Creates a mock route collection object with getRoutes().
     */
    private function createRouteCollection(array $routes): object
    {
        return new class($routes) {
            public function __construct(
                private readonly array $routes,
            ) {}

            public function getRoutes(): array
            {
                return $this->routes;
            }
        };
    }
}

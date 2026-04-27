<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Spiral\Inspector\SpiralRouteAdapter;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralRouteCollectionAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Spiral\Router\RouteInterface;
use Spiral\Router\RouterInterface;
use Spiral\Router\UriHandler;

final class SpiralRouteCollectionAdapterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        SpiralStubsBootstrap::install();
    }

    public function testWrapsEachRouteAsAdapterAndPreservesNames(): void
    {
        $route = $this->makeRoute('/users');
        $router = new class([$route]) implements RouterInterface {
            public function __construct(
                private readonly array $routes,
            ) {}

            public function getRoutes(): array
            {
                return ['users.index' => $this->routes[0]];
            }
        };

        $collection = new SpiralRouteCollectionAdapter($router);
        $routes = $collection->getRoutes();

        self::assertCount(1, $routes);
        self::assertInstanceOf(SpiralRouteAdapter::class, $routes[0]);
        $debug = $routes[0]->__debugInfo();
        self::assertSame('users.index', $debug['name']);
        self::assertSame('/users', $debug['pattern']);
    }

    public function testNonRouteEntriesAreSkipped(): void
    {
        $router = new class implements RouterInterface {
            public function getRoutes(): array
            {
                return ['bogus' => new \stdClass()];
            }
        };

        self::assertSame([], new SpiralRouteCollectionAdapter($router)->getRoutes());
    }

    public function testRouterThrowingReturnsEmptyList(): void
    {
        $router = new class implements RouterInterface {
            public function getRoutes(): array
            {
                throw new RuntimeException('boom');
            }
        };

        self::assertSame([], new SpiralRouteCollectionAdapter($router)->getRoutes());
    }

    private function makeRoute(string $pattern): RouteInterface
    {
        return new class($pattern) implements RouteInterface {
            public function __construct(
                private readonly string $pattern,
            ) {}

            public function getUriHandler(): UriHandler
            {
                return new UriHandler($this->pattern);
            }

            public function getVerbs(): array
            {
                return ['GET'];
            }

            public function getDefaults(): array
            {
                return [];
            }

            public function match(ServerRequestInterface $request): ?RouteInterface
            {
                return null;
            }

            public function getMatches(): ?array
            {
                return null;
            }
        };
    }
}

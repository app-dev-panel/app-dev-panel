<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Inspector;

use AppDevPanel\Adapter\Spiral\Inspector\SpiralMatchResult;
use AppDevPanel\Adapter\Spiral\Inspector\SpiralUrlMatcherAdapter;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Spiral\Router\RouteInterface;
use Spiral\Router\RouterInterface;
use Spiral\Router\UriHandler;

final class SpiralUrlMatcherAdapterTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        SpiralStubsBootstrap::install();
    }

    public function testMatchReturnsSuccessWithControllerActionLabel(): void
    {
        $matched = $this->matchedRouteWithDefaults(['controller' => 'App\\C', 'action' => 'index']);
        $router = $this->routerWithRoute($matched);

        $result = new SpiralUrlMatcherAdapter($router)->match(new ServerRequest('GET', '/x'));

        self::assertInstanceOf(SpiralMatchResult::class, $result);
        self::assertTrue($result->isSuccess());
        self::assertSame(['App\\C::index'], $result->middlewares);
    }

    public function testMatchWithoutControllerYieldsEmptyMiddlewares(): void
    {
        $matched = $this->matchedRouteWithDefaults([]);
        $router = $this->routerWithRoute($matched);

        $result = new SpiralUrlMatcherAdapter($router)->match(new ServerRequest('GET', '/x'));

        self::assertTrue($result->isSuccess());
        self::assertSame([], $result->middlewares);
    }

    public function testNoRouteMatchesReturnsFailure(): void
    {
        $route = $this->routeReturningMatch(null);
        $router = $this->routerWithRoute($route);

        self::assertFalse(
            new SpiralUrlMatcherAdapter($router)
                ->match(new ServerRequest('GET', '/x'))
                ->isSuccess(),
        );
    }

    public function testRouterThrowingReturnsFailure(): void
    {
        $router = new class implements RouterInterface {
            public function getRoutes(): array
            {
                throw new \RuntimeException('boom');
            }
        };

        self::assertFalse(
            new SpiralUrlMatcherAdapter($router)
                ->match(new ServerRequest('GET', '/x'))
                ->isSuccess(),
        );
    }

    private function routerWithRoute(RouteInterface $route): RouterInterface
    {
        return new class($route) implements RouterInterface {
            public function __construct(
                private readonly RouteInterface $route,
            ) {}

            public function getRoutes(): array
            {
                return [$this->route];
            }
        };
    }

    /**
     * @param array<string, mixed> $defaults
     */
    private function matchedRouteWithDefaults(array $defaults): RouteInterface
    {
        $matched = new class($defaults) implements RouteInterface {
            public function __construct(
                private readonly array $defaults,
            ) {}

            public function getUriHandler(): UriHandler
            {
                return new UriHandler();
            }

            public function getVerbs(): array
            {
                return [];
            }

            public function getDefaults(): array
            {
                return $this->defaults;
            }

            public function match(ServerRequestInterface $request): ?RouteInterface
            {
                return $this;
            }

            public function getMatches(): ?array
            {
                return [];
            }
        };

        return $this->routeReturningMatch($matched);
    }

    private function routeReturningMatch(?RouteInterface $matched): RouteInterface
    {
        return new class($matched) implements RouteInterface {
            public function __construct(
                private readonly ?RouteInterface $matched,
            ) {}

            public function getUriHandler(): UriHandler
            {
                return new UriHandler();
            }

            public function getVerbs(): array
            {
                return [];
            }

            public function getDefaults(): array
            {
                return [];
            }

            public function match(ServerRequestInterface $request): ?RouteInterface
            {
                return $this->matched;
            }

            public function getMatches(): ?array
            {
                return null;
            }
        };
    }
}

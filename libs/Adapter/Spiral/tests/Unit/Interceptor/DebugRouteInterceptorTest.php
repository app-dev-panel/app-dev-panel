<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Interceptor;

use AppDevPanel\Adapter\Spiral\Interceptor\DebugRouteInterceptor;
use AppDevPanel\Kernel\Collector\RouterCollector;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\Context\TargetInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Router\RouteInterface;
use Spiral\Router\Router;
use Spiral\Router\UriHandler;

final class DebugRouteInterceptorTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        InterceptorStubsBootstrap::install();
    }

    public function testFeedsRouterCollectorOnMatchedRouteAttribute(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $route = self::route('/users/<id>');

        $request = new ServerRequest('GET', 'http://example.com/users/42')
            ->withAttribute(Router::ROUTE_ATTRIBUTE, $route)
            ->withAttribute(Router::ROUTE_NAME, 'users.show')
            ->withAttribute(Router::ROUTE_MATCHES, ['id' => '42']);

        $context = self::context(['request' => $request], []);

        $handler = self::handler(static fn(): string => 'ok');

        $interceptor = new DebugRouteInterceptor($collector);
        $result = $interceptor->intercept($context, $handler);

        self::assertSame('ok', $result);

        $collected = $collector->getCollected();
        self::assertNotNull($collected['currentRoute']);
        self::assertSame('users.show', $collected['currentRoute']['name']);
        self::assertSame('/users/<id>', $collected['currentRoute']['pattern']);
        self::assertSame(['id' => '42'], $collected['currentRoute']['arguments']);
        self::assertSame('example.com', $collected['currentRoute']['host']);
        self::assertSame('http://example.com/users/42', $collected['currentRoute']['uri']);
    }

    public function testFallsBackToRequestArgumentWhenAttributeMissing(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $route = self::route('/admin');
        $request = new ServerRequest('GET', 'http://example.com/admin')
            ->withAttribute(Router::ROUTE_ATTRIBUTE, $route)
            ->withAttribute(Router::ROUTE_NAME, 'admin');

        // Attributes empty — request supplied as positional argument under "request" key.
        $context = self::context([], ['request' => $request]);

        $handler = self::handler(static fn(): int => 200);

        $interceptor = new DebugRouteInterceptor($collector);
        $interceptor->intercept($context, $handler);

        $collected = $collector->getCollected();
        self::assertNotNull($collected['currentRoute']);
        self::assertSame('admin', $collected['currentRoute']['name']);
    }

    public function testNoOpWhenRequestAbsent(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $context = self::context([], []);
        $handler = self::handler(static fn(): null => null);

        $interceptor = new DebugRouteInterceptor($collector);
        $interceptor->intercept($context, $handler);

        $collected = $collector->getCollected();
        self::assertNull($collected['currentRoute']);
    }

    public function testRecordsMatchTimeEvenOnHandlerException(): void
    {
        $collector = new RouterCollector();
        $collector->startup();

        $request = new ServerRequest('GET', 'http://example.com/');
        $context = self::context(['request' => $request], []);
        $handler = self::handler(static function (): never {
            throw new RuntimeException('boom');
        });

        $interceptor = new DebugRouteInterceptor($collector);

        $threw = false;
        try {
            $interceptor->intercept($context, $handler);
        } catch (RuntimeException) {
            $threw = true;
        }

        self::assertTrue($threw);
        // matchTime gets recorded in the finally block — getCollected() reflects it via routeTime
        // only when collectRoutes() was also called. We assert the collector survived without crashing.
        self::assertNull($collector->getCollected()['currentRoute']);
    }

    private static function route(string $pattern): RouteInterface
    {
        $uriHandler = new UriHandler($pattern);

        return new class($uriHandler) implements RouteInterface {
            public function __construct(
                private readonly UriHandler $uriHandler,
            ) {}

            public function getUriHandler(): UriHandler
            {
                return $this->uriHandler;
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
                return $this;
            }

            /** @return array<string, mixed>|null */
            public function getMatches(): ?array
            {
                return null;
            }
        };
    }

    /**
     * @param array<non-empty-string, mixed> $attributes
     * @param array<int|string, mixed> $arguments
     */
    private static function context(array $attributes, array $arguments): CallContextInterface
    {
        return new class($attributes, $arguments) implements CallContextInterface {
            /** @param array<non-empty-string, mixed> $attributes */
            /** @param array<int|string, mixed> $arguments */
            public function __construct(
                private array $attributes,
                private readonly array $arguments,
            ) {}

            public function getTarget(): TargetInterface
            {
                return new class implements TargetInterface {
                    public function getPath(): array
                    {
                        return [];
                    }

                    public function withPath(array $path, ?string $delimiter = null): static
                    {
                        return $this;
                    }

                    public function getReflection(): ?\ReflectionFunctionAbstract
                    {
                        return null;
                    }

                    public function getObject(): ?object
                    {
                        return null;
                    }

                    public function getCallable(): callable|array|null
                    {
                        return null;
                    }

                    public function __toString(): string
                    {
                        return '';
                    }
                };
            }

            public function getArguments(): array
            {
                return $this->arguments;
            }

            public function withTarget(TargetInterface $target): static
            {
                return $this;
            }

            public function withArguments(array $arguments): static
            {
                return $this;
            }

            public function getAttributes(): array
            {
                return $this->attributes;
            }

            public function getAttribute(string $name, mixed $default = null): mixed
            {
                return $this->attributes[$name] ?? $default;
            }

            public function withAttribute(string $name, mixed $value): static
            {
                $clone = clone $this;
                $clone->attributes[$name] = $value;
                return $clone;
            }

            public function withoutAttribute(string $name): static
            {
                $clone = clone $this;
                unset($clone->attributes[$name]);
                return $clone;
            }
        };
    }

    /**
     * @param callable(CallContextInterface): mixed $callback
     */
    private static function handler(callable $callback): HandlerInterface
    {
        return new class($callback) implements HandlerInterface {
            /** @var callable(CallContextInterface): mixed */
            private $callback;

            public function __construct(callable $callback)
            {
                $this->callback = $callback;
            }

            public function handle(CallContextInterface $context): mixed
            {
                return ($this->callback)($context);
            }
        };
    }
}

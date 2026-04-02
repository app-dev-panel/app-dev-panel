<?php

declare(strict_types=1);

namespace Unit\Command;

use AppDevPanel\Cli\Command\InspectRoutesCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class InspectRoutesCommandTest extends TestCase
{
    public function testCommandCanBeInstantiated(): void
    {
        $command = new InspectRoutesCommand();

        $this->assertSame('inspect:routes', $command->getName());
        $this->assertSame(
            'Inspect application routes: list all routes, check route matching',
            $command->getDescription(),
        );
    }

    public function testCommandHasOptions(): void
    {
        $command = new InspectRoutesCommand();
        $definition = $command->getDefinition();

        $this->assertTrue($definition->hasOption('json'));
        $this->assertTrue($definition->hasArgument('action'));
        $this->assertTrue($definition->hasArgument('path'));
    }

    public function testListRoutesWithoutFramework(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand(null, null));
        $tester->execute(['action' => 'list']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Route inspection requires framework integration', $tester->getDisplay());
    }

    public function testCheckRouteWithoutFramework(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand(null, null));
        $tester->execute(['action' => 'check', 'path' => '/test']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Route checking requires framework integration', $tester->getDisplay());
    }

    public function testCheckRouteRequiresPath(): void
    {
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Path is required', $tester->getDisplay());
    }

    public function testUnknownAction(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand());
        $tester->execute(['action' => 'invalid']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Unknown action', $tester->getDisplay());
    }

    public function testListRoutesWithRoutes(): void
    {
        $route1 = new class() {
            public function __debugInfo(): array
            {
                return [
                    'name' => 'api.users',
                    'hosts' => [],
                    'pattern' => '/api/users',
                    'methods' => ['GET', 'POST'],
                    'defaults' => [],
                    'override' => false,
                    'middlewares' => ['App\\Controller\\UserController'],
                ];
            }
        };
        $route2 = new class() {
            public function __debugInfo(): array
            {
                return [
                    'name' => 'home',
                    'hosts' => [],
                    'pattern' => '/',
                    'methods' => ['GET'],
                    'defaults' => [],
                    'override' => false,
                    'middlewareDefinitions' => ['App\\Controller\\HomeController'],
                ];
            }
        };

        $routeCollection = new class($route1, $route2) {
            private array $routes;

            public function __construct(object ...$routes)
            {
                $this->routes = $routes;
            }

            public function getRoutes(): array
            {
                return $this->routes;
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand($routeCollection));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Application Routes (2)', $display);
        $this->assertStringContainsString('api.users', $display);
        $this->assertStringContainsString('/api/users', $display);
        $this->assertStringContainsString('GET|POST', $display);
        $this->assertStringContainsString('home', $display);
    }

    public function testListRoutesEmpty(): void
    {
        $routeCollection = new class() {
            public function getRoutes(): array
            {
                return [];
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand($routeCollection));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No routes found', $tester->getDisplay());
    }

    public function testListRoutesJson(): void
    {
        $route = new class() {
            public function __debugInfo(): array
            {
                return [
                    'name' => 'test',
                    'hosts' => [],
                    'pattern' => '/test',
                    'methods' => ['GET'],
                    'defaults' => [],
                    'override' => false,
                    'middlewares' => [],
                ];
            }
        };

        $routeCollection = new class($route) {
            private array $routes;

            public function __construct(object ...$routes)
            {
                $this->routes = $routes;
            }

            public function getRoutes(): array
            {
                return $this->routes;
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand($routeCollection));
        $tester->execute(['action' => 'list', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($decoded);
        $this->assertNotEmpty($decoded);
    }

    public function testCheckRouteNoMatch(): void
    {
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/nonexistent']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No route matched for GET /nonexistent', $tester->getDisplay());
    }

    public function testCheckRouteNoMatchJson(): void
    {
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/missing', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertFalse($decoded['result']);
    }

    public function testCheckRouteMatch(): void
    {
        $matchedRoute = new class() {
            /** @var list<string> */
            public array $middlewareDefinitions = ['App\\Controller\\UserController::index'];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/api/users']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Route matched: GET /api/users', $display);
        $this->assertStringContainsString('UserController', $display);
    }

    public function testCheckRouteMatchJson(): void
    {
        $matchedRoute = new class() {
            /** @var list<string> */
            public array $middlewareDefinitions = ['App\\Controller\\HomeController'];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($decoded['result']);
        $this->assertSame('App\\Controller\\HomeController', $decoded['action']);
    }

    public function testCheckRouteWithMethodParsing(): void
    {
        $matchedRoute = new class() {
            /** @var list<string> */
            public array $middlewareDefinitions = ['App\\Controller\\UserController::store'];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => 'POST /api/users']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Route matched: POST /api/users', $tester->getDisplay());
    }

    public function testCheckRouteWithMiddlewaresFallback(): void
    {
        $matchedRoute = new class() {
            /** @var list<string> */
            public array $middlewares = ['App\\Controller\\LegacyController'];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/legacy']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Route matched: GET /legacy', $tester->getDisplay());
        $this->assertStringContainsString('LegacyController', $tester->getDisplay());
    }

    public function testListRoutesWithNonArrayMethods(): void
    {
        $route = new class() {
            public function __debugInfo(): array
            {
                return [
                    'name' => 'any-route',
                    'hosts' => [],
                    'pattern' => '/any',
                    'methods' => null,
                    'defaults' => [],
                    'override' => false,
                    'middlewares' => [],
                ];
            }
        };

        $routeCollection = new class($route) {
            private array $routes;

            public function __construct(object ...$routes)
            {
                $this->routes = $routes;
            }

            public function getRoutes(): array
            {
                return $this->routes;
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand($routeCollection));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('ANY', $tester->getDisplay());
    }

    public function testCheckRouteWithNonHttpMethodPrefix(): void
    {
        // Path starts with a word that is NOT an HTTP method — should default to GET
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => 'NOTMETHOD /foo']);

        $this->assertSame(0, $tester->getStatusCode());
        // Should treat the whole thing as the path with GET method
        $this->assertStringContainsString('No route matched for GET NOTMETHOD /foo', $tester->getDisplay());
    }

    public function testCheckRouteWithPutMethod(): void
    {
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => 'PUT /api/resource/1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No route matched for PUT /api/resource/1', $tester->getDisplay());
    }

    public function testCheckRouteWithDeleteMethod(): void
    {
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => 'DELETE /api/resource/1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('No route matched for DELETE /api/resource/1', $tester->getDisplay());
    }

    public function testCheckRouteWithPatchMethod(): void
    {
        $matchedRoute = new class() {
            /** @var list<string> */
            public array $middlewareDefinitions = ['App\\Controller\\PatchController::update'];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => 'PATCH /api/resource/1']);

        $this->assertSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('Route matched: PATCH /api/resource/1', $tester->getDisplay());
    }

    public function testCheckRouteMatchWithArrayAction(): void
    {
        $matchedRoute = new class() {
            /** @var list<mixed> */
            public array $middlewareDefinitions = [['class' => 'App\\Controller\\ApiController', 'method' => 'index']];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/api']);

        $this->assertSame(0, $tester->getStatusCode());
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Route matched: GET /api', $display);
        // Array action should be JSON-encoded
        $this->assertStringContainsString('Action:', $display);
    }

    public function testCheckRouteMatchJsonWithArrayAction(): void
    {
        $matchedRoute = new class() {
            /** @var list<mixed> */
            public array $middlewareDefinitions = [['class' => 'Controller', 'method' => 'action']];
        };

        $urlMatcher = new class($matchedRoute) {
            public function __construct(
                private readonly object $route,
            ) {}

            public function match(object $request): object
            {
                $route = $this->route;
                return new class($route) {
                    public function __construct(
                        private readonly object $route,
                    ) {}

                    public function isSuccess(): bool
                    {
                        return true;
                    }

                    public function route(): object
                    {
                        return $this->route;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '/test', '--json' => true]);

        $this->assertSame(0, $tester->getStatusCode());
        $decoded = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertTrue($decoded['result']);
        $this->assertIsArray($decoded['action']);
    }

    public function testCheckRouteEmptyPath(): void
    {
        $urlMatcher = new class() {
            public function match(object $request): object
            {
                return new class() {
                    public function isSuccess(): bool
                    {
                        return false;
                    }
                };
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand(null, $urlMatcher));
        $tester->execute(['action' => 'check', 'path' => '']);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Path is required', $tester->getDisplay());
    }

    public function testListRoutesWithMissingNameAndPattern(): void
    {
        $route = new class() {
            public function __debugInfo(): array
            {
                return [
                    'name' => null,
                    'hosts' => [],
                    'pattern' => null,
                    'methods' => ['GET'],
                    'defaults' => [],
                    'override' => false,
                    'middlewares' => [],
                ];
            }
        };

        $routeCollection = new class($route) {
            private array $routes;

            public function __construct(object ...$routes)
            {
                $this->routes = $routes;
            }

            public function getRoutes(): array
            {
                return $this->routes;
            }
        };

        $tester = new CommandTester(new InspectRoutesCommand($routeCollection));
        $tester->execute(['action' => 'list']);

        $this->assertSame(0, $tester->getStatusCode());
        // Null name/pattern should render as dashes
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Application Routes (1)', $display);
    }

    public function testDefaultActionIsList(): void
    {
        $tester = new CommandTester(new InspectRoutesCommand(null, null));
        // No action argument — should default to 'list'
        $tester->execute([]);

        $this->assertSame(1, $tester->getStatusCode());
        $this->assertStringContainsString('Route inspection requires framework integration', $tester->getDisplay());
    }

    public function testHelpText(): void
    {
        $command = new InspectRoutesCommand();
        $help = $command->getHelp();

        $this->assertStringContainsString('inspect:routes', $help);
        $this->assertStringContainsString('inspect:routes list', $help);
        $this->assertStringContainsString('inspect:routes check', $help);
    }
}

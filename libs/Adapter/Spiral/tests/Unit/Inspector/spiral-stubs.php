<?php

declare(strict_types=1);

/**
 * Compatibility stubs for Spiral packages the adapter integrates with but that are NOT
 * brought into the root `vendor/` by the project composer (only `spiral/core` is —
 * `spiral/boot`, `spiral/router`, `spiral/events` ship in the playground vendor only).
 *
 * Runtime code defends against missing classes via `interface_exists` / `class_exists`
 * guards. The unit tests `require_once` this file so they can construct realistic doubles.
 * If/when the root composer is extended to require those packages directly, every
 * `interface_exists` / `class_exists` guard below evaluates to `true` and the stubs are
 * skipped — the real classes loaded by the autoloader take precedence.
 *
 * @noinspection PhpUnused
 */

namespace Spiral\Boot {
    if (!interface_exists(EnvironmentInterface::class)) {
        interface EnvironmentInterface
        {
            public function getID(): string;

            public function set(string $name, mixed $value): self;

            public function get(string $name, mixed $default = null): mixed;

            public function getAll(): array;
        }
    }

    if (!interface_exists(DirectoriesInterface::class)) {
        interface DirectoriesInterface
        {
            public function has(string $name): bool;

            public function set(string $name, string $path): self;

            public function get(string $name): string;

            public function getAll(): array;
        }
    }
}

namespace Spiral\Boot\BootloadManager {
    if (!class_exists(ClassesRegistry::class)) {
        final class ClassesRegistry
        {
            /** @var list<string> */
            private array $classes = [];

            public function register(string $class): void
            {
                $this->classes[] = $class;
            }

            /** @return list<string> */
            public function getClasses(): array
            {
                return $this->classes;
            }
        }
    }

    if (!interface_exists(InitializerInterface::class)) {
        interface InitializerInterface
        {
            public function init(array $classes): \Generator;

            public function getRegistry(): ClassesRegistry;
        }
    }
}

namespace Spiral\Events {
    if (!interface_exists(ListenerRegistryInterface::class)) {
        interface ListenerRegistryInterface
        {
            public function addListener(string $event, callable $listener, int $priority = 0): void;
        }
    }
}

namespace Spiral\Auth {
    if (!interface_exists(TokenStorageInterface::class)) {
        interface TokenStorageInterface
        {
            public function load(string $id): ?object;
        }
    }

    if (!interface_exists(ActorProviderInterface::class)) {
        interface ActorProviderInterface
        {
            public function getActor(object $token): ?object;
        }
    }
}

namespace Spiral\Router {
    if (!interface_exists(RouterInterface::class)) {
        interface RouterInterface
        {
            /** @return array<string, RouteInterface> */
            public function getRoutes(): array;
        }
    }

    if (!interface_exists(RouteInterface::class)) {
        interface RouteInterface
        {
            public function getUriHandler(): UriHandler;

            public function getVerbs(): array;

            public function getDefaults(): array;

            public function match(\Psr\Http\Message\ServerRequestInterface $request): ?RouteInterface;

            /** @return array<string, mixed>|null */
            public function getMatches(): ?array;
        }
    }

    if (!class_exists(UriHandler::class)) {
        final class UriHandler
        {
            public function __construct(
                private readonly ?string $pattern = null,
            ) {}

            public function getPattern(): ?string
            {
                return $this->pattern;
            }
        }
    }
}

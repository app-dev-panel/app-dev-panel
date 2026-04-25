<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Symfony\Routing;

use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\Routing\RouteCollection;

/**
 * Decorates Symfony's `routing.loader` to append ADP routes to the user's
 * `RouteCollection`, removing the need for a manual `config/routes/app_dev_panel.php`.
 *
 * No-op when the inner loader is absent (compiler emits the decorator with
 * `IGNORE_ON_INVALID_REFERENCE`, e.g. in stripped containers used by tests).
 * The bundle's `adp.php` file is registered as a `FileResource` so route
 * cache invalidates correctly when its source changes.
 */
final class AdpRoutingLoaderDecorator implements LoaderInterface
{
    public function __construct(
        private readonly LoaderInterface $inner,
        private readonly string $routesPath,
    ) {}

    public function load(mixed $resource, ?string $type = null): mixed
    {
        $collection = $this->inner->load($resource, $type);

        if (!$collection instanceof RouteCollection || !is_file($this->routesPath)) {
            return $collection;
        }

        $resolver = $this->inner->getResolver();
        if ($resolver === null) {
            return $collection;
        }

        $adpLoader = $resolver->resolve($this->routesPath);
        if ($adpLoader === false) {
            return $collection;
        }

        $adpCollection = $adpLoader->load($this->routesPath);
        if (!$adpCollection instanceof RouteCollection) {
            return $collection;
        }

        $collection->addCollection($adpCollection);
        $collection->addResource(new FileResource($this->routesPath));

        return $collection;
    }

    public function supports(mixed $resource, ?string $type = null): bool
    {
        return $this->inner->supports($resource, $type);
    }

    public function getResolver(): LoaderResolverInterface
    {
        return $this->inner->getResolver();
    }

    public function setResolver(LoaderResolverInterface $resolver): void
    {
        $this->inner->setResolver($resolver);
    }
}

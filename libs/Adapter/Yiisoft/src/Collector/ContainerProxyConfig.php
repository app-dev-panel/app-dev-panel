<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Psr\EventDispatcher\EventDispatcherInterface;

use function in_array;
use function is_callable;

final class ContainerProxyConfig
{
    /**
     * @psalm-param array<string, mixed> $decoratedServices
     */
    public function __construct(
        private bool $active = false,
        private array $decoratedServices = [],
        private ?EventDispatcherInterface $dispatcher = null,
        private ?ServiceCollector $collector = null,
        private ?string $proxyCachePath = null,
        private int $logLevel = ContainerInterfaceProxy::LOG_NOTHING,
    ) {
    }

    public function activate(): self
    {
        $config = clone $this;
        $config->active = true;

        return $config;
    }

    public function withDispatcher(EventDispatcherInterface $dispatcher): self
    {
        $config = clone $this;
        $config->dispatcher = $dispatcher;

        return $config;
    }

    public function withLogLevel(int $logLevel): self
    {
        $config = clone $this;
        $config->logLevel = $logLevel;

        return $config;
    }

    public function withProxyCachePath(string $proxyCachePath): self
    {
        $config = clone $this;
        $config->proxyCachePath = $proxyCachePath;

        return $config;
    }

    public function withCollector(ServiceCollector $collector): self
    {
        $config = clone $this;
        $config->collector = $collector;

        return $config;
    }

    public function withDecoratedServices(array $decoratedServices): self
    {
        $config = clone $this;
        $config->decoratedServices = array_merge($this->decoratedServices, $decoratedServices);

        return $config;
    }

    public function getIsActive(): bool
    {
        return $this->active;
    }

    public function getLogLevel(): int
    {
        return $this->logLevel;
    }

    public function getDecoratedServices(): array
    {
        return $this->decoratedServices;
    }

    public function getDispatcher(): ?EventDispatcherInterface
    {
        return $this->dispatcher;
    }

    public function getCollector(): ?ServiceCollector
    {
        return $this->collector;
    }

    public function getProxyCachePath(): ?string
    {
        return $this->proxyCachePath;
    }

    public function getDecoratedServiceConfig(string $service): mixed
    {
        return $this->decoratedServices[$service];
    }

    public function hasDecoratedService(string $service): bool
    {
        return isset($this->decoratedServices[$service]) || in_array($service, $this->decoratedServices, true);
    }

    public function hasDecoratedServiceArrayConfigWithStringKeys(string $service): bool
    {
        return $this->hasDecoratedServiceArrayConfig($service) && !isset($this->decoratedServices[$service][0]);
    }

    public function hasDecoratedServiceArrayConfig(string $service): bool
    {
        return isset($this->decoratedServices[$service]) && is_array($this->decoratedServices[$service]);
    }

    public function hasDecoratedServiceCallableConfig(string $service): bool
    {
        return isset($this->decoratedServices[$service]) && is_callable($this->decoratedServices[$service]);
    }

    public function hasDispatcher(): bool
    {
        return $this->dispatcher !== null;
    }

    public function hasCollector(): bool
    {
        return $this->collector !== null;
    }
}

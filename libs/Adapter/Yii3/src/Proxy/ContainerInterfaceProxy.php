<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Proxy;

use AppDevPanel\Kernel\ProxyDecoratedCalls;
use Exception;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Yiisoft\Proxy\ProxyManager;
use Yiisoft\Proxy\ProxyTrait;

use function array_key_exists;
use function is_callable;
use function is_object;
use function is_string;

final class ContainerInterfaceProxy implements ContainerInterface
{
    use ProxyDecoratedCalls;
    use ProxyLogTrait;
    use ProxyTrait;

    public const LOG_NOTHING = 0;
    public const LOG_ARGUMENTS = 1;
    public const LOG_RESULT = 2;
    public const LOG_ERROR = 4;

    private ProxyManager $proxyManager;

    private array $serviceProxy = [];

    public function __construct(
        protected ContainerInterface $decorated,
        ContainerProxyConfig $config,
    ) {
        $this->config = $config;
        $this->proxyManager = new ProxyManager($this->config->getProxyCachePath());
    }

    public function withDecoratedServices(array $decoratedServices): self
    {
        $new = clone $this;
        $new->config = $this->config->withDecoratedServices($decoratedServices);
        return $new;
    }

    public function get($id): mixed
    {
        $this->resetCurrentError();
        $timeStart = microtime(true);
        $instance = null;
        try {
            $instance = $id === ContainerInterface::class ? $this : $this->decorated->get($id);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->logProxy(ContainerInterface::class, $this->decorated, 'get', [$id], $instance, $timeStart);
        }

        if (is_object($instance) && ($proxy = $this->getServiceProxy($id, $instance))) {
            $this->serviceProxy[$id] = $proxy;
            return $proxy;
        }

        return $instance;
    }

    public function isActive(): bool
    {
        return $this->config->getIsActive() && $this->config->getDecoratedServices() !== [];
    }

    private function getServiceProxy(string $service, object $instance): ?object
    {
        if (array_key_exists($service, $this->serviceProxy)) {
            return $this->serviceProxy[$service];
        }

        if (!$this->isActive() || !$this->config->hasDecoratedService($service)) {
            return null;
        }

        return match ($this->config->getServiceConfigType($service)) {
            ServiceConfigType::Callable => $this->config->getDecoratedServiceConfig($service)($this, $instance),
            ServiceConfigType::MethodCallbacks => $this->createMethodProxy($service, $instance),
            ServiceConfigType::ArrayDefinition => $this->createArrayProxy(
                $instance,
                $this->config->getDecoratedServiceConfig($service),
            ),
            ServiceConfigType::None => $this->createInterfaceProxy($service, $instance),
        };
    }

    private function createInterfaceProxy(string $service, object $instance): ?object
    {
        if (!interface_exists($service) || !($this->config->hasCollector() || $this->config->hasDispatcher())) {
            return null;
        }

        return $this->proxyManager->createObjectProxy($service, ServiceProxy::class, [
            $service,
            $instance,
            $this->config,
        ]);
    }

    private function createMethodProxy(string $service, object $instance): ?object
    {
        $callbacks = $this->config->getDecoratedServiceConfig($service);
        $proxyTarget = interface_exists($service) || class_exists($service) ? $service : $instance::class;
        $methods = [];
        foreach ($callbacks as $method => $callback) {
            if (!is_string($method) || !is_callable($callback)) {
                continue;
            }
            $methods[$method] = $callback;
        }

        return $this->proxyManager->createObjectProxy($proxyTarget, ServiceMethodProxy::class, [
            $proxyTarget,
            $instance,
            $methods,
            $this->config,
        ]);
    }

    private function createArrayProxy(object $instance, array $params): ?object
    {
        try {
            $proxyClass = array_shift($params);
            foreach ($params as $index => $param) {
                if (!is_string($param)) {
                    continue;
                }
                try {
                    $params[$index] = $this->get($param);
                } catch (Exception) {
                    // Silently ignore: parameter may not be a resolvable service ID.
                    // Keep the original string value as-is.
                    continue;
                }
            }
            return new $proxyClass($instance, ...$params);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * @psalm-suppress InvalidCatch
     */
    public function has($id): bool
    {
        $this->resetCurrentError();
        $timeStart = microtime(true);
        $result = null;

        try {
            $result = $this->decorated->has($id);
        } catch (ContainerExceptionInterface $e) {
            $this->repeatError($e);
        } finally {
            $this->logProxy(ContainerInterface::class, $this->decorated, 'has', [$id], $result, $timeStart);
        }

        return (bool) $result;
    }
}

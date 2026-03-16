<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use AppDevPanel\Kernel\Proxy\AbstractObjectProxy;
use AppDevPanel\Kernel\Proxy\ErrorHandlingTrait;

class ServiceProxy extends AbstractObjectProxy
{
    use ProxyLogTrait;
    use ErrorHandlingTrait;

    public function __construct(
        private readonly string $service,
        object $instance,
        ContainerProxyConfig $config,
    ) {
        $this->config = $config;
        parent::__construct($instance);
    }

    protected function afterCall(string $methodName, array $arguments, mixed $result, float $timeStart): mixed
    {
        $this->logProxy($this->service, $this->getInstance(), $methodName, $arguments, $result, $timeStart);
        return $result;
    }

    protected function getNewStaticInstance(object $instance): static
    {
        return new static($this->service, $instance, $this->config);
    }

    protected function getService(): string
    {
        return $this->service;
    }

    protected function getConfig(): ContainerProxyConfig
    {
        return $this->config;
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Yiisoft\Proxy\ObjectProxy;

class ServiceMethodProxy extends ServiceProxy
{
    public function __construct(
        string $service,
        object $instance,
        private readonly array $methods,
        ContainerProxyConfig $config
    ) {
        parent::__construct($service, $instance, $config);
    }

    protected function afterCall(string $methodName, array $arguments, mixed $result, float $timeStart): mixed
    {
        try {
            if (isset($this->methods[$methodName])) {
                $callback = $this->methods[$methodName];
                $result = $callback($result, ...$arguments);
            }
        } finally {
            $this->logProxy($this->getService(), $this->getInstance(), $methodName, $arguments, $result, $timeStart);
        }

        return $result;
    }

    protected function getNewStaticInstance(object $instance): ObjectProxy
    {
        /**
         * @psalm-suppress UnsafeInstantiation Constructor should be consistent to `getNewStaticInstance()`.
         */
        return new static($this->getService(), $instance, $this->methods, $this->getConfig());
    }
}

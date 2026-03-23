<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yiisoft\Proxy;

use AppDevPanel\Kernel\Event\MethodCallRecord;
use AppDevPanel\Kernel\Event\ProxyMethodCallEvent;

trait ProxyLogTrait
{
    private ContainerProxyConfig $config;

    protected function logProxy(
        string $service,
        object $instance,
        string $method,
        array $arguments,
        mixed $result,
        float $timeStart,
    ): void {
        $error = $this->getCurrentError();
        $this->processLogData($arguments, $result, $error);

        $record = new MethodCallRecord(
            $service,
            $instance::class,
            $method,
            $arguments,
            $result,
            $this->getCurrentResultStatus(),
            $error,
            $timeStart,
            microtime(true),
        );

        $this->config->getCollector()?->collect($record);
        if ($this->config->getDispatcher() !== null) {
            $this->config->getDispatcher()->dispatch(new ProxyMethodCallEvent($record));
        }
    }

    /**
     * @psalm-param-out array|null $arguments
     */
    private function processLogData(array &$arguments, mixed &$result, ?object &$error): void
    {
        if (!($this->config->getLogLevel() & ContainerInterfaceProxy::LOG_ARGUMENTS)) {
            $arguments = null;
        }

        if (!($this->config->getLogLevel() & ContainerInterfaceProxy::LOG_RESULT)) {
            $result = null;
        }

        if (!($this->config->getLogLevel() & ContainerInterfaceProxy::LOG_ERROR)) {
            $error = null;
        }
    }

    private function getCurrentResultStatus(): string
    {
        if (!$this->hasCurrentError()) {
            return 'success';
        }

        return 'failed';
    }
}

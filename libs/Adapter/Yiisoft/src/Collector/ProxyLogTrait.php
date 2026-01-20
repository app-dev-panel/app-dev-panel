<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug\Collector;

use Yiisoft\Yii\Debug\Event\ProxyMethodCallEvent;

trait ProxyLogTrait
{
    private ContainerProxyConfig $config;

    protected function logProxy(
        string $service,
        object $instance,
        string $method,
        array $arguments,
        mixed $result,
        float $timeStart
    ): void {
        $error = $this->getCurrentError();
        $this->processLogData($arguments, $result, $error);

        if ($this->config->getCollector() !== null) {
            $this->logToCollector($service, $instance, $method, $arguments, $result, $error, $timeStart);
        }

        if ($this->config->getDispatcher() !== null) {
            $this->logToEvent($service, $instance, $method, $arguments, $result, $error, $timeStart);
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

    private function logToCollector(
        string $service,
        object $instance,
        string $method,
        ?array $arguments,
        mixed $result,
        ?object $error,
        float $timeStart
    ): void {
        $this->config->getCollector()?->collect(
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
    }

    private function logToEvent(
        string $service,
        object $instance,
        string $method,
        ?array $arguments,
        mixed $result,
        ?object $error,
        float $timeStart
    ): void {
        $this->config->getDispatcher()?->dispatch(
            new ProxyMethodCallEvent(
                $service,
                $instance::class,
                $method,
                $arguments,
                $result,
                $this->getCurrentResultStatus(),
                $error,
                $timeStart,
                microtime(true),
            )
        );
    }

    private function getCurrentResultStatus(): string
    {
        if (!$this->hasCurrentError()) {
            return 'success';
        }

        return 'failed';
    }
}

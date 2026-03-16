<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Proxy;

use Throwable;

/**
 * Provides error tracking for proxy classes.
 * Replaces Yiisoft\Proxy\ProxyTrait error handling functionality.
 */
trait ErrorHandlingTrait
{
    private ?Throwable $currentError = null;

    protected function resetCurrentError(): void
    {
        $this->currentError = null;
    }

    public function getCurrentError(): ?Throwable
    {
        return $this->currentError;
    }

    protected function hasCurrentError(): bool
    {
        return $this->currentError !== null;
    }

    /**
     * @throws Throwable
     */
    protected function repeatError(Throwable $error): never
    {
        $this->currentError = $error;
        throw $error;
    }
}

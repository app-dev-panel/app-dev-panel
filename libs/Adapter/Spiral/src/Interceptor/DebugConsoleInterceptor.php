<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Interceptor;

use AppDevPanel\Kernel\Collector\Console\CommandCollector;
use AppDevPanel\Kernel\Collector\Console\ConsoleAppInfoCollector;
use AppDevPanel\Kernel\Collector\ExceptionCollector;
use AppDevPanel\Kernel\Debugger;
use AppDevPanel\Kernel\StartupContext;
use Spiral\Interceptors\Context\CallContextInterface;
use Spiral\Interceptors\HandlerInterface;
use Spiral\Interceptors\InterceptorInterface;
use Throwable;

/**
 * Wraps the Spiral console pipeline so each command run becomes its own ADP debug
 * entry — the CLI flow has no PSR-15 middleware path, so the interceptor manages
 * `Debugger::startup()/shutdown()` directly.
 *
 * Registered via `Spiral\Console\Bootloader\ConsoleBootloader::addInterceptor()` from
 * the {@see \AppDevPanel\Adapter\Spiral\Bootloader\AdpInterceptorBootloader} only when
 * `spiral/console` is installed. The command name is derived from the
 * {@see CallContextInterface::getTarget()} reflection / object so it matches whatever
 * Spiral resolved (Symfony command name, target path) for the run.
 */
final class DebugConsoleInterceptor implements InterceptorInterface
{
    public function __construct(
        private readonly Debugger $debugger,
        private readonly CommandCollector $commandCollector,
        private readonly ConsoleAppInfoCollector $appInfoCollector,
        private readonly ExceptionCollector $exceptionCollector,
    ) {}

    public function intercept(CallContextInterface $context, HandlerInterface $handler): mixed
    {
        $name = $this->extractCommandName($context);
        $input = $this->stringifyArguments($context->getArguments());

        $this->debugger->startup(StartupContext::forCommand($name));
        $this->appInfoCollector->markApplicationStarted();
        $this->commandCollector->collectCommandData(['name' => $name, 'input' => $input]);

        try {
            $result = $handler->handle($context);
            $this->recordSuccess($name, $input, $result);
            return $result;
        } catch (Throwable $error) {
            $this->recordError($name, $input, $error);
            throw $error;
        } finally {
            $this->appInfoCollector->markApplicationFinished();
            $this->debugger->shutdown();
        }
    }

    private function recordSuccess(string $name, ?string $input, mixed $result): void
    {
        $this->commandCollector->collectCommandData([
            'name' => $name,
            'input' => $input,
            'exitCode' => is_int($result) ? $result : 0,
        ]);
    }

    private function recordError(string $name, ?string $input, Throwable $error): void
    {
        $this->exceptionCollector->collect($error);
        $this->commandCollector->collectCommandData([
            'name' => $name,
            'input' => $input,
            'exitCode' => 1,
            'error' => $error->getMessage(),
        ]);
    }

    private function extractCommandName(CallContextInterface $context): string
    {
        $target = $context->getTarget();

        $fromObject = $this->extractNameFromObject($target->getObject());
        if ($fromObject !== null) {
            return $fromObject;
        }

        $path = $target->getPath();
        if ($path !== []) {
            return implode('::', $path);
        }

        $attributeName = $context->getAttribute('command_name');
        return is_string($attributeName) && $attributeName !== '' ? $attributeName : 'unknown';
    }

    private function extractNameFromObject(?object $object): ?string
    {
        if ($object === null || !method_exists($object, 'getName')) {
            return null;
        }
        $candidate = $object->getName();
        return is_string($candidate) && $candidate !== '' ? $candidate : null;
    }

    /**
     * @param array<int|string, mixed> $arguments
     */
    private function stringifyArguments(array $arguments): ?string
    {
        if ($arguments === []) {
            return null;
        }

        $parts = [];
        foreach ($arguments as $key => $value) {
            $parts[] = is_int($key) ? $this->renderScalar($value) : $key . '=' . $this->renderScalar($value);
        }

        return implode(' ', $parts);
    }

    private function renderScalar(mixed $value): string
    {
        return match (true) {
            $value === null => '',
            is_scalar($value) => (string) $value,
            is_array($value) => 'array(' . count($value) . ')',
            is_object($value) => $value::class,
            default => get_debug_type($value),
        };
    }
}

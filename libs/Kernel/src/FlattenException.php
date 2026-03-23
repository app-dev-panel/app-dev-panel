<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use __PHP_Incomplete_Class;
use ArrayObject;
use Exception;
use Stringable;
use Throwable;

/**
 * FlattenException wraps a PHP Exception to be able to serialize it.
 * Implements the Throwable interface
 * Basically, this class removes all objects from the trace.
 * Ported from Symfony components @link https://github.com/symfony/symfony/blob/master/src/Symfony/Component/Debug/Exception/FlattenException.php
 */
final class FlattenException implements Stringable
{
    private readonly string $message;
    private readonly mixed $code;
    private readonly string $file;
    private readonly int $line;
    private readonly ?FlattenException $previous;
    private readonly array $trace;
    private readonly string $toString;
    private readonly string $class;

    public function __construct(Throwable $exception)
    {
        $this->message = $exception->getMessage();
        $this->code = $exception->getCode();
        $this->file = $exception->getFile();
        $this->line = $exception->getLine();
        $this->trace = $this->flattenTrace($exception->getTrace());
        $this->toString = $exception->__toString();
        $this->class = $exception::class;

        $previous = $exception->getPrevious();
        $this->previous = $previous instanceof Exception ? new self($previous) : null;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): mixed
    {
        return $this->code;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getTrace(): array
    {
        return $this->trace;
    }

    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    public function getTraceAsString(): string
    {
        $remove = "Stack trace:\n";
        $len = strpos($this->toString, $remove);
        if ($len === false) {
            return '';
        }
        return substr($this->toString, $len + strlen($remove));
    }

    public function __toString(): string
    {
        return $this->toString;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * Flattens exception trace into a serializable array.
     *
     * @psalm-param list<array{args?: array, class?: class-string, file?: string, function?: string, line?: int, type?: string}> $trace
     */
    private function flattenTrace(array $trace): array
    {
        $result = [];
        foreach ($trace as $entry) {
            $class = '';
            $namespace = '';
            if (array_key_exists('class', $entry)) {
                $parts = explode('\\', $entry['class']);
                $class = array_pop($parts);
                $namespace = implode('\\', $parts);
            }

            $result[] = [
                'namespace' => $namespace,
                'short_class' => $class,
                'class' => $entry['class'] ?? '',
                'type' => $entry['type'] ?? '',
                'function' => $entry['function'] ?? null,
                'file' => $entry['file'] ?? null,
                'line' => $entry['line'] ?? null,
                'args' => array_key_exists('args', $entry) ? $this->flattenArgs($entry['args']) : [],
            ];
        }
        return $result;
    }

    /**
     * Serializes trace arguments by replacing objects/resources with type descriptors.
     */
    private function flattenArgs(array $args, int $level = 0, int &$count = 0): array
    {
        $result = [];
        foreach ($args as $key => $value) {
            if (++$count > 10_000) {
                return ['array', '*SKIPPED over 10000 entries*'];
            }
            $result[$key] = match (true) {
                $value instanceof __PHP_Incomplete_Class => [
                    'incomplete-object',
                    $this->getClassNameFromIncomplete($value),
                ],
                is_object($value) => ['object', $value::class],
                is_array($value) => $level > 10
                    ? ['array', '*DEEP NESTED ARRAY*']
                    : ['array', $this->flattenArgs($value, $level + 1, $count)],
                null === $value => ['null', null],
                is_bool($value) => ['boolean', $value],
                is_int($value) => ['integer', $value],
                is_float($value) => ['float', $value],
                is_resource($value) => ['resource', get_resource_type($value)],
                default => ['string', (string) $value],
            };
        }

        return $result;
    }

    private function getClassNameFromIncomplete(__PHP_Incomplete_Class $value): string
    {
        $array = new ArrayObject($value);

        return $array['__PHP_Incomplete_Class_Name'];
    }
}

<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

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
    /**
     * @var string
     */
    private string $message;
    /**
     * @var int|mixed
     */
    private mixed $code;
    /**
     * @var string
     */
    private string $file;
    /**
     * @var int
     */
    private int $line;

    /**
     * @var FlattenException|null
     */
    private ?FlattenException $previous = null;
    /**
     * @var array
     */
    private array $trace;
    /**
     * @var string
     */
    private string $toString;
    /**
     * @var string
     */
    private string $class;

    /**
     * FlattenException constructor.
     */
    public function __construct(Throwable $exception)
    {
        $this->setMessage($exception->getMessage());
        $this->setCode($exception->getCode());
        $this->setFile($exception->getFile());
        $this->setLine($exception->getLine());
        $this->setTrace($exception->getTrace());
        $this->setToString($exception->__toString());
        $this->setClass($exception::class);

        $previous = $exception->getPrevious();
        if ($previous instanceof Exception) {
            $this->setPrevious(new self($previous));
        }
    }

    /**
     * @param string $string the string representation of the thrown object.
     */
    private function setToString(string $string): void
    {
        $this->toString = $string;
    }

    /**
     * Gets the Exception message
     *
     * @return string the Exception message as a string.
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message the Exception message as a string.
     */
    private function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * Gets the Exception code
     *
     * @return int|mixed the exception code as integer.
     */
    public function getCode(): mixed
    {
        return $this->code;
    }

    /**
     * @param int|mixed $code the exception code as integer.
     */
    private function setCode(mixed $code): void
    {
        $this->code = $code;
    }

    /**
     * Gets the file in which the exception occurred
     *
     * @return string the filename in which the exception was created.
     */
    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @param string $file the filename in which the exception was created.
     */
    private function setFile(string $file): void
    {
        $this->file = $file;
    }

    /**
     * Gets the line in which the exception occurred
     *
     * @return int the line number where the exception was created.
     */
    public function getLine(): int
    {
        return $this->line;
    }

    /**
     * @param int $line the line number where the exception was created.
     */
    private function setLine(int $line): void
    {
        $this->line = $line;
    }

    /**
     * Gets the stack trace
     *
     * @return array the Exception stack trace as an array.
     */
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * @param array $trace the Exception stack trace as an array.
     *
     * @psalm-param list<
     *     array{
     *          args?: array,
     *          class?: class-string,
     *          file?: string,
     *          function?: string,
     *          line?: int,
     *          type?: string
     *     }
     * > $trace
     */
    private function setTrace(array $trace): void
    {
        $this->trace = [];
        foreach ($trace as $entry) {
            $class = '';
            $namespace = '';
            if (isset($entry['class'])) {
                $parts = explode('\\', $entry['class']);
                $class = array_pop($parts);
                $namespace = implode('\\', $parts);
            }

            $this->trace[] = [
                'namespace' => $namespace,
                'short_class' => $class,
                'class' => $entry['class'] ?? '',
                'type' => $entry['type'] ?? '',
                'function' => $entry['function'] ?? null,
                'file' => $entry['file'] ?? null,
                'line' => $entry['line'] ?? null,
                'args' => isset($entry['args']) ? $this->flattenArgs($entry['args']) : [],
            ];
        }
    }

    /**
     * Returns previous Exception
     *
     * @return FlattenException|null the previous `FlattenException` if available or null otherwise.
     */
    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    /**
     * @param FlattenException $previous previous Exception.
     */
    private function setPrevious(self $previous): void
    {
        $this->previous = $previous;
    }

    /**
     * Gets the stack trace as a string
     *
     * @return string the Exception stack trace as a string.
     */
    public function getTraceAsString(): string
    {
        $remove = "Stack trace:\n";
        $len = strpos($this->toString, $remove);
        if ($len === false) {
            return '';
        }
        return substr($this->toString, $len + strlen($remove));
    }

    /**
     * String representation of the exception
     *
     * @return string the string representation of the exception.
     */
    public function __toString(): string
    {
        return $this->toString;
    }

    /**
     * @return string the name of the class in which the exception was created.
     */
    public function getClass(): string
    {
        return $this->class;
    }

    /**
     * @param string $class the name of the class in which the exception was created.
     */
    private function setClass(string $class): void
    {
        $this->class = $class;
    }

    /**
     * Allows you to sterilize the Exception trace arguments
     *
     * @param int $level recursion level
     * @param int $count number of records counter
     *
     * @return array arguments tracing.
     */
    private function flattenArgs(array $args, int $level = 0, int &$count = 0): array
    {
        $result = [];
        foreach ($args as $key => $value) {
            if (++$count > 10000) {
                return ['array', '*SKIPPED over 10000 entries*'];
            }
            if ($value instanceof __PHP_Incomplete_Class) {
                // is_object() returns false on PHP<=7.1
                $result[$key] = ['incomplete-object', $this->getClassNameFromIncomplete($value)];
            } elseif (is_object($value)) {
                $result[$key] = ['object', $value::class];
            } elseif (is_array($value)) {
                if ($level > 10) {
                    $result[$key] = ['array', '*DEEP NESTED ARRAY*'];
                } else {
                    $result[$key] = ['array', $this->flattenArgs($value, $level + 1, $count)];
                }
            } elseif (null === $value) {
                $result[$key] = ['null', null];
            } elseif (is_bool($value)) {
                $result[$key] = ['boolean', $value];
            } elseif (is_int($value)) {
                $result[$key] = ['integer', $value];
            } elseif (is_float($value)) {
                $result[$key] = ['float', $value];
            } elseif (is_resource($value)) {
                $result[$key] = ['resource', get_resource_type($value)];
            } else {
                $result[$key] = ['string', (string)$value];
            }
        }

        return $result;
    }

    /**
     * @return string the real class name of an incomplete class
     */
    private function getClassNameFromIncomplete(__PHP_Incomplete_Class $value): string
    {
        $array = new ArrayObject($value);

        return $array['__PHP_Incomplete_Class_Name'];
    }
}

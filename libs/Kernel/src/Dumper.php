<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use AppDevPanel\Kernel\Inspector\ClosureDescriptor;

final class Dumper
{
    private readonly DumpContext $context;

    private function __construct(
        private readonly mixed $variable,
        array $excludedClasses,
    ) {
        $this->context = new DumpContext([], array_flip($excludedClasses));
    }

    /**
     * @return self An instance containing variable to dump.
     */
    public static function create(mixed $variable, array $excludedClasses = []): self
    {
        return new self($variable, $excludedClasses);
    }

    /**
     * Export variable as JSON.
     *
     * @param int $depth Maximum depth that the dumper should go into the variable.
     * @param bool $format Whatever to format exported code.
     *
     * @return string JSON string.
     */
    public function asJson(int $depth = 50, bool $format = false): string
    {
        $this->context->buildObjectsCache($this->variable, $depth);
        return $this->encodeJson($this->context->dumpNestedInternal($this->variable, $depth, 0, 0, false), $format);
    }

    /**
     * Export variable as JSON summary of topmost items.
     * Dumper goes into the variable full depth to search all objects.
     *
     * @param int $depth Maximum depth that the dumper should print out arrays.
     * @param bool $prettyPrint Whatever to format exported code.
     *
     * @return string JSON string containing summary.
     */
    public function asJsonObjectsMap(int $depth = 50, bool $prettyPrint = false): string
    {
        $this->context->buildObjectsCache($this->variable);
        return $this->encodeJson(
            $this->context->dumpNestedInternal($this->context->objects, $depth + 2, 0, 1, true),
            $prettyPrint,
        );
    }

    private function encodeJson(mixed $data, bool $format): string
    {
        $options = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

        if ($format) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode(self::sanitizeForJson($data), $options);
    }

    /**
     * Recursively replace any value that json_encode cannot represent with
     * a descriptive placeholder string. Handles resources, NAN, INF, closures,
     * and other non-encodable types without silently dropping data.
     */
    private static function sanitizeForJson(mixed $value): mixed
    {
        if (is_resource($value)) {
            return sprintf('(resource: %s, id=%d)', get_resource_type($value), (int) $value);
        }

        if ($value === null || is_bool($value) || is_int($value) || is_string($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (is_nan($value)) {
                return '(nan)';
            }
            if (!is_finite($value)) {
                return $value > 0 ? '(inf)' : '(-inf)';
            }
            return $value;
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::sanitizeForJson($v);
            }
            return $out;
        }

        if ($value instanceof \Closure) {
            return ClosureDescriptor::describe($value);
        }

        if (is_object($value)) {
            // Any object reaching this point (Dumper normally converts objects to arrays)
            // is a stray reference — serialize as a description rather than letting
            // json_encode attempt (and possibly fail on) its properties.
            return sprintf('(object: %s#%d)', $value::class, spl_object_id($value));
        }

        // __PHP_Incomplete_Class and anything else unknown.
        return sprintf('(unserializable: %s)', gettype($value));
    }
}

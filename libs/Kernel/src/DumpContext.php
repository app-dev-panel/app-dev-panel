<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel;

use __PHP_Incomplete_Class;
use AppDevPanel\Kernel\Inspector\ClosureDescriptor;
use Closure;

use function array_key_exists;
use function is_array;
use function is_object;

final class DumpContext
{
    public function __construct(
        public array $objects,
        private readonly array $excludedClasses,
    ) {}

    public function dumpNestedInternal(
        mixed $variable,
        int $depth,
        int $level,
        int $objectCollapseLevel,
        bool $inlineObject,
    ): mixed {
        return match (gettype($variable)) {
            'array' => $this->dumpArray($variable, $depth, $level, $objectCollapseLevel, $inlineObject),
            'object' => $this->dumpObject($variable, $depth, $level, $objectCollapseLevel, $inlineObject),
            'resource', 'resource (closed)' => $this->getResourceDescription($variable),
            default => $variable,
        };
    }

    public function buildObjectsCache(mixed $variable, ?int $depth = null, int $level = 0): void
    {
        if (is_object($variable)) {
            if (
                array_key_exists($variable::class, $this->excludedClasses)
                || array_key_exists($objectDescription = $this->getObjectDescription($variable), $this->objects)
            ) {
                return;
            }
            $this->objects[$objectDescription] = $variable;
        }

        $nextLevel = $level + 1;
        if ($depth !== null && $depth <= $nextLevel) {
            return;
        }

        if (is_object($variable)) {
            $variable = $this->getObjectProperties($variable);
            foreach ($variable as $value) {
                $this->buildObjectsCache($value, $depth, $nextLevel);
            }
            return;
        }

        if (is_array($variable)) {
            foreach ($variable as $value) {
                $this->buildObjectsCache($value, $depth, $nextLevel);
            }
        }
    }

    public function getObjectDescription(object $object): string
    {
        if (str_contains($object::class, '@anonymous')) {
            return 'class@anonymous#' . spl_object_id($object);
        }
        return $object::class . '#' . spl_object_id($object);
    }

    private function dumpArray(
        array $variable,
        int $depth,
        int $level,
        int $objectCollapseLevel,
        bool $inlineObject,
    ): array|string {
        if ($depth <= $level) {
            $valuesCount = count($variable);
            if ($valuesCount === 0) {
                return [];
            }
            return sprintf('array (%d %s) [...]', $valuesCount, $valuesCount === 1 ? 'item' : 'items');
        }

        $output = [];
        foreach ($variable as $key => $value) {
            $keyDisplay = str_replace("\0", '::', trim((string) $key));
            $output[$keyDisplay] = $this->dumpNestedInternal(
                $value,
                $depth,
                $level + 1,
                $objectCollapseLevel,
                $inlineObject,
            );
        }

        return $output;
    }

    private function dumpObject(
        object $variable,
        int $depth,
        int $level,
        int $objectCollapseLevel,
        bool $inlineObject,
    ): mixed {
        $objectDescription = $this->getObjectDescription($variable);

        if ($variable instanceof Closure) {
            $descriptor = ClosureDescriptor::describe($variable);
            return $inlineObject ? $descriptor : [$objectDescription => $descriptor];
        }

        if ($objectCollapseLevel < $level && array_key_exists($objectDescription, $this->objects)) {
            return 'object@' . $objectDescription;
        }

        if (
            $depth <= $level
            || array_key_exists($variable::class, $this->excludedClasses)
            || !array_key_exists($objectDescription, $this->objects)
        ) {
            return $objectDescription . ' (...)';
        }

        $properties = $this->getObjectProperties($variable);
        if ($properties === []) {
            return $inlineObject ? '{stateless object}' : [$objectDescription => '{stateless object}'];
        }

        $output = [];
        foreach ($properties as $key => $value) {
            $keyDisplay = $this->normalizeProperty((string) $key);
            /**
             * @psalm-suppress InvalidArrayOffset
             */
            $output[$keyDisplay] = $this->dumpNestedInternal(
                $value,
                $depth,
                $level + 1,
                $objectCollapseLevel,
                $inlineObject,
            );
        }

        return $inlineObject ? $output : [$objectDescription => $output];
    }

    private function getObjectProperties(object $var): array
    {
        if (__PHP_Incomplete_Class::class !== $var::class && method_exists($var, '__debugInfo')) {
            $var = $var->__debugInfo();
        }

        return (array) $var;
    }

    private function normalizeProperty(string $property): string
    {
        $property = str_replace("\0", '::', trim($property));

        if (str_starts_with($property, '*::')) {
            return 'protected $' . substr($property, 3);
        }

        if (($pos = strpos($property, '::')) !== false) {
            return 'private $' . substr($property, $pos + 2);
        }

        return 'public $' . $property;
    }

    private function getResourceDescription(mixed $resource): array|string
    {
        if (!is_resource($resource)) {
            return '{closed resource}';
        }

        $type = get_resource_type($resource);
        if ($type === 'stream') {
            return stream_get_meta_data($resource);
        }
        if ($type !== '') {
            return sprintf('{%s resource}', $type);
        }

        return '{resource}';
    }
}

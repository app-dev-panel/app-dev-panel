<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Debug;

use __PHP_Incomplete_Class;
use Closure;
use ReflectionException;
use Yiisoft\VarDumper\ClosureExporter;

use function array_key_exists;
use function is_array;
use function is_object;

final class Dumper
{
    private array $objects = [];

    private static ?ClosureExporter $closureExporter = null;
    private readonly array $excludedClasses;

    private function __construct(
        private readonly mixed $variable,
        array $excludedClasses
    ) {
        $this->excludedClasses = array_flip($excludedClasses);
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
        $this->buildObjectsCache($this->variable, $depth);
        return $this->asJsonInternal($this->variable, $format, $depth, 0, false);
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
        $this->buildObjectsCache($this->variable);
        return $this->asJsonInternal($this->objects, $prettyPrint, $depth + 2, 1, true);
    }

    private function buildObjectsCache(mixed $variable, ?int $depth = null, int $level = 0): void
    {
        if (is_object($variable)) {
            if (array_key_exists($variable::class, $this->excludedClasses) ||
                array_key_exists($objectDescription = $this->getObjectDescription($variable), $this->objects)
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

    private function asJsonInternal(
        mixed $variable,
        bool $format,
        int $depth,
        int $objectCollapseLevel,
        bool $inlineObject,
    ): string {
        $options = JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE;

        if ($format) {
            $options |= JSON_PRETTY_PRINT;
        }

        return json_encode(
            $this->dumpNestedInternal($variable, $depth, 0, $objectCollapseLevel, $inlineObject),
            $options,
        );
    }

    private function getObjectProperties(object $var): array
    {
        if (__PHP_Incomplete_Class::class !== $var::class && method_exists($var, '__debugInfo')) {
            $var = $var->__debugInfo();
        }

        return (array) $var;
    }

    private function dumpNestedInternal(
        mixed $variable,
        int $depth,
        int $level,
        int $objectCollapseLevel,
        bool $inlineObject
    ): mixed {
        switch (gettype($variable)) {
            case 'array':
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
                        $inlineObject
                    );
                }

                break;
            case 'object':
                $objectDescription = $this->getObjectDescription($variable);

                if ($variable instanceof Closure) {
                    $output = $inlineObject
                        ? $this->exportClosure($variable)
                        : [$objectDescription => $this->exportClosure($variable)];
                    break;
                }

                if ($objectCollapseLevel < $level && array_key_exists($objectDescription, $this->objects)) {
                    $output = 'object@' . $objectDescription;
                    break;
                }

                if (
                    $depth <= $level
                    || array_key_exists($variable::class, $this->excludedClasses)
                    || !array_key_exists($objectDescription, $this->objects)
                ) {
                    $output = $objectDescription . ' (...)';
                    break;
                }

                $properties = $this->getObjectProperties($variable);
                if (empty($properties)) {
                    if ($inlineObject) {
                        $output = '{stateless object}';
                        break;
                    }
                    $output = [$objectDescription => '{stateless object}'];
                    break;
                }
                $output = [];
                foreach ($properties as $key => $value) {
                    $keyDisplay = $this->normalizeProperty((string) $key);
                    /**
                     * @psalm-suppress InvalidArrayOffset
                     */
                    $output[$objectDescription][$keyDisplay] = $this->dumpNestedInternal(
                        $value,
                        $depth,
                        $level + 1,
                        $objectCollapseLevel,
                        $inlineObject,
                    );
                }
                if ($inlineObject) {
                    $output = $output[$objectDescription];
                }
                break;
            case 'resource':
            case 'resource (closed)':
                $output = $this->getResourceDescription($variable);
                break;
            default:
                $output = $variable;
        }

        return $output;
    }

    private function getObjectDescription(object $object): string
    {
        if (str_contains($object::class, '@anonymous')) {
            return 'class@anonymous#' . spl_object_id($object);
        }
        return $object::class . '#' . spl_object_id($object);
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
        if (!empty($type)) {
            return sprintf('{%s resource}', $type);
        }

        return '{resource}';
    }

    /**
     * Exports a {@see Closure} instance.
     *
     * @param Closure $closure Closure instance.
     *
     * @throws ReflectionException
     */
    private function exportClosure(Closure $closure): string
    {
        return (self::$closureExporter ??= new ClosureExporter())->export($closure);
    }
}

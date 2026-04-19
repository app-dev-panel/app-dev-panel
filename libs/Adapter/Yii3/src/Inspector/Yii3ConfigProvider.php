<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Yii3\Inspector;

use AppDevPanel\Kernel\Inspector\ClosureDescriptorTrait;
use Yiisoft\Config\ConfigInterface;

/**
 * Provides configuration data from Yii 3's merged config for the ADP inspector.
 *
 * Registered as the 'config' service alias so that
 * {@see \AppDevPanel\Api\Inspector\Controller\InspectController} can inspect app config.
 *
 * For the 'events' / 'events-web' groups the raw callables returned by
 * {@see ConfigInterface::get()} are normalised to the shape understood by the
 * frontend Events page: a list of `{name, class, listeners}` entries where each
 * listener is a string (`Class::method`), a `[class, method]` tuple, or a
 * `ClosureDescriptor` array for anonymous functions/arrow functions.
 */
final class Yii3ConfigProvider
{
    use ClosureDescriptorTrait;

    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    /**
     * @return array<mixed>
     */
    public function get(string $group): array
    {
        return match ($group) {
            'events', 'events-web', 'events-console' => $this->getEventListeners($group),
            default => $this->config->get($group),
        };
    }

    /**
     * @return list<array{name: string, class: string|null, listeners: list<string|array>}>
     */
    private function getEventListeners(string $group): array
    {
        try {
            $listeners = $this->config->get($group);
        } catch (\Throwable) {
            return [];
        }

        ksort($listeners);

        $result = [];
        foreach ($listeners as $eventName => $eventListeners) {
            if (!is_string($eventName) || !is_array($eventListeners)) {
                continue;
            }

            $result[] = [
                'name' => $eventName,
                'class' => class_exists($eventName) || interface_exists($eventName) ? $eventName : null,
                'listeners' => array_map($this->describeListener(...), array_values($eventListeners)),
            ];
        }

        return $result;
    }

    /**
     * @return string|array{__closure: true, source: string, file: string|null, startLine: int|null, endLine: int|null}
     */
    private function describeListener(mixed $listener): string|array
    {
        if (is_string($listener)) {
            return $listener;
        }
        if (is_array($listener) && count($listener) === 2 && array_is_list($listener)) {
            $class = is_object($listener[0]) ? $listener[0]::class : (string) $listener[0];
            return $class . '::' . (string) $listener[1];
        }
        if ($listener instanceof \Closure) {
            return self::describeClosure($listener);
        }
        if (is_object($listener) && method_exists($listener, '__invoke')) {
            return $listener::class . '::__invoke';
        }
        return get_debug_type($listener);
    }
}

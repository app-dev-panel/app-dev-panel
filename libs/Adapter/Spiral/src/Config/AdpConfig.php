<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Config;

use Spiral\Core\InjectableConfig;

/**
 * Spiral-style typed configuration for the ADP adapter.
 *
 * Loaded by Spiral's `ConfigsInterface` (parent `InjectableConfig::INJECTOR`):
 * an `app-dev-panel.php` file in the application's `app/config/` directory
 * supplies overrides via `defaultLoaders` (PHP / JSON). Overrides applied via
 * the constructor (`new AdpConfig(['storage' => ['path' => '/foo']])`) merge
 * shallowly on top of the defaults, matching `InjectableConfig::__construct()`.
 *
 * Each accessor falls back to an `APP_DEV_PANEL_*` env var when the config
 * value is left at its `null` default. This matters for setups that run the
 * bootloader without a config file (the playground does not ship `app/config/`).
 *
 * Defaults:
 *
 *   enabled            true
 *   storage.path       null  → APP_DEV_PANEL_STORAGE_PATH or sys_get_temp_dir()/app-dev-panel
 *   storage.history_size 50
 *   panel.static_url   null  → APP_DEV_PANEL_STATIC_URL or PanelConfig::DEFAULT_STATIC_URL
 *   panel.base_path    /debug
 *   ignored_requests   ['/debug/api/**', '/debug', '/inspect/api/**']
 *   ignored_commands   ['help', 'list', 'completion']
 *   collectors         every collector enabled by default
 */
final class AdpConfig extends InjectableConfig
{
    public const CONFIG = 'app-dev-panel';

    /**
     * @var array{
     *     enabled: bool,
     *     storage: array{path: string|null, history_size: int},
     *     panel: array{static_url: string|null, base_path: string},
     *     ignored_requests: list<string>,
     *     ignored_commands: list<string>,
     *     collectors: array<string, bool>,
     * }
     */
    protected array $config = [
        'enabled' => true,
        'storage' => [
            'path' => null,
            'history_size' => 50,
        ],
        'panel' => [
            'static_url' => null,
            'base_path' => '/debug',
        ],
        'ignored_requests' => ['/debug/api/**', '/debug', '/inspect/api/**'],
        'ignored_commands' => ['help', 'list', 'completion'],
        'collectors' => [
            'log' => true,
            'event' => true,
            'exception' => true,
            'http_client' => true,
            'var_dumper' => true,
            'timeline' => true,
            'request' => true,
            'web_app' => true,
            'filesystem' => true,
            'cache' => true,
            'router' => true,
            'validator' => true,
            'translator' => true,
            'template' => true,
            'mailer' => true,
            'queue' => true,
            'database' => true,
        ],
    ];

    public function isEnabled(): bool
    {
        return (bool) $this->config['enabled'];
    }

    public function storagePath(): string
    {
        $explicit = $this->config['storage']['path'] ?? null;
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $env = getenv('APP_DEV_PANEL_STORAGE_PATH');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        return sys_get_temp_dir() . '/app-dev-panel';
    }

    public function historySize(): int
    {
        return (int) ($this->config['storage']['history_size'] ?? 50);
    }

    public function staticUrl(): ?string
    {
        $explicit = $this->config['panel']['static_url'] ?? null;
        if (is_string($explicit) && $explicit !== '') {
            return $explicit;
        }

        $env = getenv('APP_DEV_PANEL_STATIC_URL');
        if (is_string($env) && $env !== '') {
            return $env;
        }

        return null;
    }

    public function basePath(): string
    {
        $value = $this->config['panel']['base_path'] ?? '/debug';

        return is_string($value) && $value !== '' ? $value : '/debug';
    }

    public function isCollectorEnabled(string $name): bool
    {
        $collectors = $this->config['collectors'] ?? [];
        if (!is_array($collectors) || !array_key_exists($name, $collectors)) {
            return false;
        }

        return (bool) $collectors[$name];
    }

    /** @return list<string> */
    public function ignoredRequests(): array
    {
        $value = $this->config['ignored_requests'] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn(mixed $v): bool => is_string($v)));
    }

    /** @return list<string> */
    public function ignoredCommands(): array
    {
        $value = $this->config['ignored_commands'] ?? [];
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn(mixed $v): bool => is_string($v)));
    }
}

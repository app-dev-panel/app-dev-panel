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
 *
 * One accessor per config key (each with its env-var fallback) is the
 * `InjectableConfig` contract — splitting the class by option group would hide
 * the single config-file schema, hence the method count / complexity pragmas.
 */
// @mago-expect lint:too-many-methods,cyclomatic-complexity
final class AdpConfig extends InjectableConfig
{
    public const CONFIG = 'app-dev-panel';

    /**
     * Default configuration values, shape (parent declares `array<array-key, mixed>`):
     *
     *   enabled              bool
     *   storage              array{path: string|null, history_size: int}
     *   project_config_path  string|null
     *   panel                array{static_url: string|null, base_path: string}
     *   ignored_requests     list<string>
     *   ignored_commands     list<string>
     *   collectors           array<string, bool>
     */
    protected array $config = [
        'enabled' => true,
        'storage' => [
            'path' => null,
            'history_size' => 50,
        ],
        // Directory holding the committable project config (frames, OpenAPI specs).
        // Default `null` falls back to APP_DEV_PANEL_PROJECT_CONFIG_PATH or
        // `<cwd>/app/config/adp`, matching Spiral's `app/config/` convention.
        'project_config_path' => null,
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
        return (
            self::firstNonEmptyString($this->config['storage']['path'] ?? null, getenv('APP_DEV_PANEL_STORAGE_PATH'))
            ?? sys_get_temp_dir() . '/app-dev-panel'
        );
    }

    public function historySize(): int
    {
        return (int) ($this->config['storage']['history_size'] ?? 50);
    }

    /**
     * Directory holding the committable project config (frames, OpenAPI specs).
     *
     * Resolution order:
     *   1. Explicit `project_config_path` in `app/config/app-dev-panel.php`.
     *   2. `APP_DEV_PANEL_PROJECT_CONFIG_PATH` env var (lets the playground
     *      and integration tests pin the path without writing a config file).
     *   3. `APP_DEV_PANEL_ROOT_PATH . '/app/config/adp'` — same root the
     *      `PathResolver` already uses; matches Spiral's `app/config/` convention
     *      and keeps the file out of the publicly served docroot under `php -S`.
     *   4. `<cwd>/app/config/adp` as a last resort (CLI usage outside Spiral).
     *
     * The bootloader passes this string to {@see FileProjectConfigStorage},
     * which auto-creates the directory and writes a `.gitignore` next to
     * `project.json` excluding the future `secrets.json`.
     */
    public function projectConfigPath(): string
    {
        $rootPath = self::firstNonEmptyString(getenv('APP_DEV_PANEL_ROOT_PATH')) ?? (string) getcwd();

        return (
            self::firstNonEmptyString(
                $this->config['project_config_path'] ?? null,
                getenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH'),
            )
            ?? rtrim($rootPath, '/\\') . '/app/config/adp'
        );
    }

    public function staticUrl(): ?string
    {
        return self::firstNonEmptyString(
            $this->config['panel']['static_url'] ?? null,
            getenv('APP_DEV_PANEL_STATIC_URL'),
        );
    }

    public function basePath(): string
    {
        return self::firstNonEmptyString($this->config['panel']['base_path'] ?? null) ?? '/debug';
    }

    public function isCollectorEnabled(string $name): bool
    {
        $collectors = $this->config['collectors'] ?? [];

        return is_array($collectors) && (bool) ($collectors[$name] ?? false);
    }

    /** @return list<string> */
    public function ignoredRequests(): array
    {
        return self::stringList($this->config['ignored_requests'] ?? []);
    }

    /** @return list<string> */
    public function ignoredCommands(): array
    {
        return self::stringList($this->config['ignored_commands'] ?? []);
    }

    /**
     * First candidate that is a non-empty string — config values win over env vars,
     * and `getenv()`'s `false` for unset variables is skipped like an empty string.
     */
    private static function firstNonEmptyString(mixed ...$candidates): ?string
    {
        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        return null;
    }

    /** @return list<string> */
    private static function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn(mixed $v): bool => is_string($v)));
    }
}

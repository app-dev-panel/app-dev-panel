<?php

declare(strict_types=1);

namespace AppDevPanel\Adapter\Spiral\Tests\Unit\Config;

use AppDevPanel\Adapter\Spiral\Config\AdpConfig;
use PHPUnit\Framework\TestCase;

final class AdpConfigTest extends TestCase
{
    private string $previousStoragePath = '';
    private string $previousStaticUrl = '';
    private string $previousProjectConfigPath = '';
    private string $previousRootPath = '';

    protected function setUp(): void
    {
        // Snapshot env so tests don't leak state.
        $storage = getenv('APP_DEV_PANEL_STORAGE_PATH');
        $this->previousStoragePath = is_string($storage) ? $storage : '';
        $static = getenv('APP_DEV_PANEL_STATIC_URL');
        $this->previousStaticUrl = is_string($static) ? $static : '';
        $project = getenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH');
        $this->previousProjectConfigPath = is_string($project) ? $project : '';
        $root = getenv('APP_DEV_PANEL_ROOT_PATH');
        $this->previousRootPath = is_string($root) ? $root : '';

        // Tests must start with a clean env so config defaults are observable.
        putenv('APP_DEV_PANEL_STORAGE_PATH');
        putenv('APP_DEV_PANEL_STATIC_URL');
        putenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH');
        putenv('APP_DEV_PANEL_ROOT_PATH');
    }

    protected function tearDown(): void
    {
        if ($this->previousStoragePath !== '') {
            putenv('APP_DEV_PANEL_STORAGE_PATH=' . $this->previousStoragePath);
        } else {
            putenv('APP_DEV_PANEL_STORAGE_PATH');
        }
        if ($this->previousStaticUrl !== '') {
            putenv('APP_DEV_PANEL_STATIC_URL=' . $this->previousStaticUrl);
        } else {
            putenv('APP_DEV_PANEL_STATIC_URL');
        }
        if ($this->previousProjectConfigPath !== '') {
            putenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH=' . $this->previousProjectConfigPath);
        } else {
            putenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH');
        }
        if ($this->previousRootPath !== '') {
            putenv('APP_DEV_PANEL_ROOT_PATH=' . $this->previousRootPath);
        } else {
            putenv('APP_DEV_PANEL_ROOT_PATH');
        }
    }

    public function testDefaultsAreReturnedWhenNoOverrides(): void
    {
        $config = new AdpConfig();

        self::assertTrue($config->isEnabled());
        self::assertSame(50, $config->historySize());
        self::assertSame('/debug', $config->basePath());
        self::assertNull($config->staticUrl());
        self::assertSame(sys_get_temp_dir() . '/app-dev-panel', $config->storagePath());
    }

    public function testDefaultIgnorePatterns(): void
    {
        $config = new AdpConfig();

        self::assertSame(['/debug/api/**', '/debug', '/inspect/api/**'], $config->ignoredRequests());
        self::assertSame(['help', 'list', 'completion'], $config->ignoredCommands());
    }

    public function testCollectorEnabledFlagsDefaultToTrueForKnownCollectors(): void
    {
        $config = new AdpConfig();

        foreach ([
            'log',
            'event',
            'exception',
            'http_client',
            'var_dumper',
            'timeline',
            'request',
            'web_app',
            'filesystem',
            'cache',
            'router',
            'validator',
            'translator',
            'template',
            'mailer',
            'queue',
            'database',
        ] as $name) {
            self::assertTrue($config->isCollectorEnabled($name), "expected '$name' to default to true");
        }
    }

    public function testIsCollectorEnabledReturnsFalseForUnknownName(): void
    {
        $config = new AdpConfig();

        self::assertFalse($config->isCollectorEnabled('nonexistent'));
    }

    public function testConstructorOverridesMergeOverDefaults(): void
    {
        $config = new AdpConfig([
            'enabled' => false,
            'storage' => ['path' => '/var/cache/adp', 'history_size' => 10],
            'panel' => ['static_url' => 'http://cdn.example.com', 'base_path' => '/adp'],
            'ignored_requests' => ['/_profiler/**'],
            'ignored_commands' => [],
            'collectors' => ['log' => false, 'event' => true],
        ]);

        self::assertFalse($config->isEnabled());
        self::assertSame('/var/cache/adp', $config->storagePath());
        self::assertSame(10, $config->historySize());
        self::assertSame('http://cdn.example.com', $config->staticUrl());
        self::assertSame('/adp', $config->basePath());
        self::assertSame(['/_profiler/**'], $config->ignoredRequests());
        self::assertSame([], $config->ignoredCommands());
        self::assertFalse($config->isCollectorEnabled('log'));
        self::assertTrue($config->isCollectorEnabled('event'));
    }

    public function testStoragePathFallsBackToEnvVar(): void
    {
        putenv('APP_DEV_PANEL_STORAGE_PATH=/tmp/from-env');

        try {
            $config = new AdpConfig();
            self::assertSame('/tmp/from-env', $config->storagePath());
        } finally {
            putenv('APP_DEV_PANEL_STORAGE_PATH');
        }
    }

    public function testExplicitStoragePathBeatsEnvVar(): void
    {
        putenv('APP_DEV_PANEL_STORAGE_PATH=/tmp/from-env');

        try {
            $config = new AdpConfig(['storage' => ['path' => '/tmp/explicit']]);
            self::assertSame('/tmp/explicit', $config->storagePath());
        } finally {
            putenv('APP_DEV_PANEL_STORAGE_PATH');
        }
    }

    public function testStaticUrlFallsBackToEnvVar(): void
    {
        putenv('APP_DEV_PANEL_STATIC_URL=/local-panel');

        try {
            $config = new AdpConfig();
            self::assertSame('/local-panel', $config->staticUrl());
        } finally {
            putenv('APP_DEV_PANEL_STATIC_URL');
        }
    }

    public function testStaticUrlIsNullWhenNeitherConfigNorEnvSet(): void
    {
        $config = new AdpConfig();

        self::assertNull($config->staticUrl());
    }

    public function testProjectConfigPathDefaultsToAppConfigAdpUnderCwd(): void
    {
        $config = new AdpConfig();

        self::assertSame((string) getcwd() . '/app/config/adp', $config->projectConfigPath());
    }

    public function testProjectConfigPathFallsBackToEnvVar(): void
    {
        putenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH=/tmp/from-env/adp');

        $config = new AdpConfig();
        self::assertSame('/tmp/from-env/adp', $config->projectConfigPath());
    }

    public function testProjectConfigPathFallsBackToRootPathEnv(): void
    {
        putenv('APP_DEV_PANEL_ROOT_PATH=/srv/app');

        $config = new AdpConfig();
        self::assertSame('/srv/app/app/config/adp', $config->projectConfigPath());
    }

    public function testProjectConfigPathPrefersExplicitEnvOverRootPath(): void
    {
        putenv('APP_DEV_PANEL_ROOT_PATH=/srv/app');
        putenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH=/tmp/from-env/adp');

        $config = new AdpConfig();
        self::assertSame('/tmp/from-env/adp', $config->projectConfigPath());
    }

    public function testExplicitProjectConfigPathBeatsEverything(): void
    {
        putenv('APP_DEV_PANEL_PROJECT_CONFIG_PATH=/tmp/from-env/adp');
        putenv('APP_DEV_PANEL_ROOT_PATH=/srv/app');

        $config = new AdpConfig(['project_config_path' => '/tmp/explicit/adp']);
        self::assertSame('/tmp/explicit/adp', $config->projectConfigPath());
    }

    public function testIgnoredRequestsRejectsNonStringEntries(): void
    {
        $config = new AdpConfig(['ignored_requests' => ['/keep', 42, null, '/also-keep']]);

        self::assertSame(['/keep', '/also-keep'], $config->ignoredRequests());
    }
}

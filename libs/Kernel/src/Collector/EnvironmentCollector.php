<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Collects runtime environment information: PHP version, extensions, SAPI, OS, working directory,
 * server parameters, and environment variables.
 *
 * This is a "common" collector — it works for both web and console contexts.
 * Server parameters can be fed from a PSR-7 request or fall back to $_SERVER.
 */
final class EnvironmentCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    /**
     * @var array<string, mixed>
     */
    private array $serverParams = [];

    /**
     * @var array<string, mixed>
     */
    private array $envVars = [];

    public function getCollected(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'php' => $this->collectPhpInfo(),
            'os' => $this->collectOsInfo(),
            'server' => $this->serverParams,
            'env' => $this->envVars,
        ];
    }

    /**
     * Populate server/env data from a PSR-7 request (preferred in web context).
     */
    public function collectFromRequest(ServerRequestInterface $request): void
    {
        if (!$this->isActive()) {
            return;
        }

        /** @var array<string, mixed> $serverParams */
        $serverParams = $request->getServerParams();
        $this->serverParams = $serverParams;
        $this->envVars = $this->collectEnvVars();
    }

    /**
     * Populate server/env data from superglobals (console or fallback).
     */
    public function collectFromGlobals(): void
    {
        if (!$this->isActive()) {
            return;
        }

        $this->serverParams = $_SERVER;
        $this->envVars = $this->collectEnvVars();
    }

    public function getSummary(): array
    {
        if (!$this->isActive()) {
            return [];
        }

        return [
            'environment' => [
                'php' => [
                    'version' => PHP_VERSION,
                    'sapi' => PHP_SAPI,
                ],
                'os' => PHP_OS_FAMILY,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectPhpInfo(): array
    {
        $extensions = get_loaded_extensions();
        sort($extensions);

        return [
            'version' => PHP_VERSION,
            'sapi' => PHP_SAPI,
            'binary' => PHP_BINARY,
            'os' => PHP_OS,
            'cwd' => getcwd() ?: null,
            'extensions' => $extensions,
            'xdebug' => $this->extensionVersion('xdebug'),
            'opcache' => $this->extensionVersion('Zend OPcache'),
            'pcov' => $this->extensionVersion('pcov'),
            'ini' => $this->collectIniSettings(),
            'zend_extensions' => get_loaded_extensions(true),
        ];
    }

    /**
     * Returns extension version string if loaded, false otherwise.
     */
    /**
     * @param non-empty-string $name
     */
    private function extensionVersion(string $name): string|false
    {
        if (!extension_loaded($name)) {
            return false;
        }

        return phpversion($name) ?: '0.0.0';
    }

    /**
     * @return array<string, mixed>
     */
    private function collectIniSettings(): array
    {
        return [
            'loaded' => php_ini_loaded_file() ?: null,
            'scanned' => php_ini_scanned_files() ?: null,
            'memory_limit' => ini_get('memory_limit') ?: null,
            'max_execution_time' => ini_get('max_execution_time') ?: null,
            'display_errors' => ini_get('display_errors') ?: null,
            'error_reporting' => error_reporting(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectOsInfo(): array
    {
        return [
            'family' => PHP_OS_FAMILY,
            'name' => PHP_OS,
            'uname' => php_uname(),
            'hostname' => gethostname() ?: null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function collectEnvVars(): array
    {
        $env = getenv();

        ksort($env);

        return $env;
    }

    private function reset(): void
    {
        $this->serverParams = [];
        $this->envVars = [];
    }
}

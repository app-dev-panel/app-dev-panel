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
        return [
            'php' => $this->collectPhpInfo(),
            'os' => $this->collectOsInfo(),
            'git' => $this->collectGitInfo(),
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
        $gitInfo = $this->collectGitInfo();

        return [
            'environment' => [
                'php' => [
                    'version' => PHP_VERSION,
                    'sapi' => PHP_SAPI,
                ],
                'os' => PHP_OS_FAMILY,
                'git' => [
                    'branch' => $gitInfo['branch'],
                    'commit' => $gitInfo['commit'],
                ],
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
     * @return array<string, string|null>
     */
    private function collectGitInfo(): array
    {
        $cwd = getcwd() ?: null;

        return [
            'branch' => $this->runGitCommand('git rev-parse --abbrev-ref HEAD', $cwd),
            'commit' => $this->runGitCommand('git rev-parse --short HEAD', $cwd),
            'commitFull' => $this->runGitCommand('git rev-parse HEAD', $cwd),
        ];
    }

    private function runGitCommand(string $command, ?string $cwd): ?string
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptors, $pipes, $cwd);

        if (!is_resource($process)) {
            return null;
        }

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($exitCode !== 0 || $output === false) {
            return null;
        }

        $result = trim($output);

        return $result !== '' ? $result : null;
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

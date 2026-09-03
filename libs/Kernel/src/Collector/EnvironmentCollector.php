<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Collector;

use AppDevPanel\Kernel\Helper\BoundedProcess;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Collects runtime environment information: PHP version, extensions, SAPI, OS, working directory,
 * server parameters, and environment variables.
 *
 * This is a "common" collector — it works for both web and console contexts.
 * Server parameters can be fed from a PSR-7 request or fall back to $_SERVER.
 *
 * Values of environment variables and server parameters whose key matches
 * {@see self::DEFAULT_SENSITIVE_KEY_PATTERN} are replaced by {@see self::REDACTED};
 * the key names are kept so the panel still shows what is configured.
 */
final class EnvironmentCollector implements SummaryCollectorInterface
{
    use CollectorTrait;

    public const string DEFAULT_SENSITIVE_KEY_PATTERN = '/(PASS|PASSWORD|SECRET|TOKEN|KEY|DSN|CREDENTIAL|PRIVATE|AUTH)/i';

    public const string REDACTED = '***';

    /**
     * Upper bound for a single `git` invocation, in seconds.
     */
    public const float DEFAULT_GIT_TIMEOUT = 5.0;

    /**
     * @var array<string, mixed>
     */
    private array $serverParams = [];

    /**
     * @var array<string, mixed>
     */
    private array $envVars = [];

    /**
     * @var array<string, string|null>|null Memoised per request: `getCollected()` and `getSummary()` both need it.
     */
    private ?array $gitInfo = null;

    /**
     * @param string|null $sensitiveKeyPattern PCRE matched against env/server keys; matching values are redacted.
     *                                         `null` disables redaction.
     * @param float $gitTimeout Maximum seconds to wait for each `git` subprocess (capped at 5s).
     * @param string $gitBinary Command prefix used to invoke git (overridable for tests).
     */
    public function __construct(
        private readonly ?string $sensitiveKeyPattern = self::DEFAULT_SENSITIVE_KEY_PATTERN,
        private readonly float $gitTimeout = self::DEFAULT_GIT_TIMEOUT,
        private readonly string $gitBinary = 'git',
    ) {}

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
        $this->serverParams = $this->redact($serverParams);
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

        $this->serverParams = $this->redact($_SERVER);
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
     *
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
        if ($this->gitInfo !== null) {
            return $this->gitInfo;
        }

        $cwd = getcwd() ?: null;

        return $this->gitInfo = [
            'branch' => $this->runGitCommand('rev-parse --abbrev-ref HEAD', $cwd),
            'commit' => $this->runGitCommand('rev-parse --short HEAD', $cwd),
            'commitFull' => $this->runGitCommand('rev-parse HEAD', $cwd),
        ];
    }

    private function runGitCommand(string $arguments, ?string $cwd): ?string
    {
        $timeout = min($this->gitTimeout, self::DEFAULT_GIT_TIMEOUT);
        $result = BoundedProcess::run($this->gitBinary . ' ' . $arguments, $cwd, $timeout);

        return $result === '' ? null : $result;
    }

    /**
     * @return array<string, mixed>
     */
    private function collectEnvVars(): array
    {
        $env = getenv();

        ksort($env);

        return $this->redact($env);
    }

    /**
     * @param array<array-key, mixed> $values
     *
     * @return array<array-key, mixed>
     */
    private function redact(array $values): array
    {
        if ($this->sensitiveKeyPattern === null) {
            return $values;
        }

        foreach (array_keys($values) as $key) {
            if (!is_string($key) || preg_match($this->sensitiveKeyPattern, $key) !== 1) {
                continue;
            }
            $values[$key] = self::REDACTED;
        }

        return $values;
    }

    private function reset(): void
    {
        $this->serverParams = [];
        $this->envVars = [];
        $this->gitInfo = null;
    }
}

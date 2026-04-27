<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

use SensitiveParameter;

/**
 * Per-machine, never-committed configuration: API keys, OAuth tokens, ACP
 * environment overrides. Lives next to {@see ProjectConfig} on disk
 * (`secrets.json` in the same directory) and is auto-listed in the
 * `.gitignore` written by {@see FileProjectConfigStorage}.
 *
 * The shape mirrors the historic `runtime/.llm-settings.json` exactly,
 * wrapped in an `llm` namespace so we can grow the file with future secret
 * categories (database creds, OAuth tokens for other providers, …) without
 * a schema migration.
 *
 * Immutable — mutators return a new instance via {@see withLlm()}.
 */
final class SecretsConfig
{
    public const int CURRENT_VERSION = 1;

    /**
     * @param array{
     *     apiKey?: string|null,
     *     provider?: string,
     *     model?: string|null,
     *     timeout?: int,
     *     customPrompt?: string,
     *     acpCommand?: string,
     *     acpArgs?: list<string>,
     *     acpEnv?: array<string, string>,
     * } $llm
     */
    public function __construct(
        #[SensitiveParameter]
        public readonly array $llm = [],
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    /**
     * Build from a JSON-decoded payload. Unknown keys are ignored, malformed
     * values are dropped silently — same forgiving load semantics as
     * {@see ProjectConfig::fromArray()}.
     */
    public static function fromArray(array $data): self
    {
        $llm = is_array($data['llm'] ?? null) ? self::normaliseLlm($data['llm']) : [];

        return new self($llm);
    }

    /**
     * @return array{version: int, llm: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'version' => self::CURRENT_VERSION,
            'llm' => $this->llm,
        ];
    }

    /**
     * Returns a copy with the LLM section replaced. Pass an empty array to
     * clear all LLM secrets at once (used by `LlmController::disconnect()`).
     *
     * @param array<string, mixed> $llm
     */
    public function withLlm(#[SensitiveParameter] array $llm): self
    {
        return new self(self::normaliseLlm($llm));
    }

    /**
     * Apply a partial update — only keys present in `$patch` are touched.
     * `null` values delete the corresponding key (used by the PATCH endpoint
     * to support explicit clears). Missing keys are left alone.
     *
     * @param array<string, mixed> $patch
     */
    public function withLlmPatch(#[SensitiveParameter] array $patch): self
    {
        $merged = $this->llm;
        foreach ($patch as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if ($value === null) {
                unset($merged[$key]);
                continue;
            }
            $merged[$key] = $value;
        }

        return new self(self::normaliseLlm($merged));
    }

    /**
     * @param array<array-key, mixed> $llm
     * @return array{
     *     apiKey?: string|null,
     *     provider?: string,
     *     model?: string|null,
     *     timeout?: int,
     *     customPrompt?: string,
     *     acpCommand?: string,
     *     acpArgs?: list<string>,
     *     acpEnv?: array<string, string>,
     * }
     */
    private static function normaliseLlm(array $llm): array
    {
        $result = [];

        if (array_key_exists('apiKey', $llm)) {
            $value = $llm['apiKey'];
            $result['apiKey'] = is_string($value) && $value !== '' ? $value : null;
        }
        if (isset($llm['provider']) && is_string($llm['provider'])) {
            $result['provider'] = $llm['provider'];
        }
        if (array_key_exists('model', $llm)) {
            $value = $llm['model'];
            $result['model'] = is_string($value) && $value !== '' ? $value : null;
        }
        if (
            isset($llm['timeout'])
            && (is_int($llm['timeout']) || is_string($llm['timeout']) && ctype_digit($llm['timeout']))
        ) {
            $result['timeout'] = (int) $llm['timeout'];
        }
        if (isset($llm['customPrompt']) && is_string($llm['customPrompt'])) {
            $result['customPrompt'] = $llm['customPrompt'];
        }
        if (isset($llm['acpCommand']) && is_string($llm['acpCommand'])) {
            $result['acpCommand'] = $llm['acpCommand'];
        }
        if (isset($llm['acpArgs']) && is_array($llm['acpArgs'])) {
            $args = [];
            foreach ($llm['acpArgs'] as $arg) {
                if (is_string($arg)) {
                    $args[] = $arg;
                }
            }
            $result['acpArgs'] = $args;
        }
        if (isset($llm['acpEnv']) && is_array($llm['acpEnv'])) {
            $env = [];
            foreach ($llm['acpEnv'] as $key => $value) {
                if (is_string($key) && is_string($value)) {
                    $env[$key] = $value;
                }
            }
            $result['acpEnv'] = $env;
        }

        return $result;
    }
}

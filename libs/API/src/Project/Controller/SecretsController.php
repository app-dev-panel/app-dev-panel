<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Project\Controller;

use AppDevPanel\Api\Http\JsonResponseFactoryInterface;
use AppDevPanel\Kernel\Project\SecretsStorageInterface;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Manages the local-only secrets file (`<configDir>/secrets.json`).
 *
 * - GET returns a *masked* document — sensitive values are never sent in the
 *   clear, only their last 4 characters plus boolean presence flags. This
 *   means a frontend can render "key configured (sk-…wxyz)" without ever
 *   loading the real key into the SPA's state.
 * - PATCH applies a merge update: only fields present in the body are
 *   touched, `null` deletes a key, missing keys are left alone. There is no
 *   PUT — the masked GET document is non-roundtrippable, so a full PUT
 *   would clobber unmodified secrets if the client echoed it back.
 */
final class SecretsController
{
    private const string MASK_TAIL = '...';

    public function __construct(
        private readonly JsonResponseFactoryInterface $responseFactory,
        private readonly SecretsStorageInterface $storage,
    ) {}

    /**
     * GET /debug/api/project/secrets — masked snapshot of the secrets file.
     */
    public function index(ServerRequestInterface $request): ResponseInterface
    {
        $config = $this->storage->load();

        return $this->responseFactory->createJsonResponse([
            'secrets' => $this->maskLlm($config->llm),
            'configDir' => $this->storage->getConfigDir(),
        ]);
    }

    /**
     * PATCH /debug/api/project/secrets — merge update.
     *
     * Body shape: `{llm: {<field>: <value-or-null>, ...}}`. `null` removes
     * the key; omitted keys stay as-is. Only `llm` is recognised today;
     * unknown top-level keys are ignored so future secret categories can be
     * added without breaking older clients.
     */
    public function patch(ServerRequestInterface $request): ResponseInterface
    {
        try {
            /** @var mixed $payload */
            $payload = json_decode((string) $request->getBody(), true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            return $this->responseFactory->createJsonResponse(['error' => 'Invalid JSON: ' . $e->getMessage()], 400);
        }

        if (!is_array($payload)) {
            return $this->responseFactory->createJsonResponse(['error' => 'Request body must be a JSON object.'], 400);
        }

        $config = $this->storage->load();
        if (isset($payload['llm']) && is_array($payload['llm'])) {
            $config = $config->withLlmPatch($payload['llm']);
        }

        $this->storage->save($config);

        return $this->responseFactory->createJsonResponse([
            'secrets' => $this->maskLlm($config->llm),
            'configDir' => $this->storage->getConfigDir(),
        ]);
    }

    /**
     * Replace sensitive fields with masked previews, keep non-secret
     * preferences (provider/model/timeout/customPrompt/acpCommand) intact.
     *
     * Sensitive: `apiKey`, every value of `acpEnv`, every entry of `acpArgs`.
     * The frontend reads booleans like `hasApiKey` to decide which inputs to
     * render in "configured" vs "empty" state.
     *
     * @param array<string, mixed> $llm
     * @return array<string, mixed>
     */
    private function maskLlm(array $llm): array
    {
        $apiKey = $llm['apiKey'] ?? null;
        $acpEnv = $llm['acpEnv'] ?? [];
        $acpArgs = $llm['acpArgs'] ?? [];

        $masked = $llm;

        $masked['apiKey'] = is_string($apiKey) && $apiKey !== '' ? $this->maskString($apiKey) : null;
        $masked['hasApiKey'] = is_string($apiKey) && $apiKey !== '';

        if (is_array($acpEnv)) {
            $maskedEnv = [];
            foreach ($acpEnv as $key => $value) {
                $maskedEnv[$key] = is_string($value) && $value !== '' ? $this->maskString($value) : '';
            }
            $masked['acpEnv'] = $maskedEnv;
        }

        // ACP args may carry secret tokens (e.g. `--api-key=sk-…`); mask them
        // wholesale rather than trying to detect which positional args are
        // sensitive. The frontend treats acpArgs as opaque and shows them
        // in a "configured / empty" widget.
        if (is_array($acpArgs)) {
            $masked['acpArgs'] = array_values(array_map(fn(mixed $arg) => is_string($arg) && $arg !== ''
                ? $this->maskString($arg)
                : '', $acpArgs));
            $masked['hasAcpArgs'] = $acpArgs !== [];
        }

        return $masked;
    }

    /**
     * Show the last four characters of a secret prefixed with `...`. Strings
     * shorter than 5 chars get fully obfuscated to avoid leaking entropy.
     */
    private function maskString(string $value): string
    {
        $length = strlen($value);
        if ($length <= 4) {
            return self::MASK_TAIL;
        }

        return self::MASK_TAIL . substr($value, -4);
    }
}

<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Llm;

/**
 * Builds hidden system prompts for LLM providers and injects them into an
 * outgoing message chain. Handles both the browser-derived context
 * (URL / selected debug entry / environment) and the user-configured custom
 * prompt, plus the provider-specific placement — `system` role where
 * supported, merged into the first user message otherwise.
 *
 * Extracted from `LlmController` so the controller stays focused on HTTP
 * request validation and delegation.
 */
final class LlmContextBuilder
{
    /**
     * @param array<int, array{role: string, content: string}> $messages
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function prependBrowserContext(array $messages, string $provider, ?array $context): array
    {
        if ($context === null || $context === []) {
            return $messages;
        }

        $lines = [
            'Browser context for the user who is chatting with you (do not mention this block unless asked):',
        ];

        $url = $this->stringField($context, 'url');
        if ($url !== null) {
            $lines[] = '- URL: ' . $url;
            foreach ($this->parseUrlQueryContext($url) as $line) {
                $lines[] = $line;
            }
        }

        $this->appendStringLine($lines, $context, 'title', 'Page title');
        $this->appendStringLine($lines, $context, 'userAgent', 'User agent');
        $this->appendStringLine($lines, $context, 'language', 'Language');
        $this->appendStringLine($lines, $context, 'timezone', 'Timezone');
        $this->appendSizeLine($lines, $context, 'viewport', 'Viewport');
        $this->appendSizeLine($lines, $context, 'screen', 'Screen', includeDpr: true);
        $this->appendStringLine($lines, $context, 'theme', 'Theme');
        $this->appendStringLine($lines, $context, 'referrer', 'Referrer');

        if (count($lines) === 1) {
            return $messages;
        }

        return $this->injectPromptPrefix($messages, $provider, implode("\n", $lines), '[%s]');
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     *
     * @return array<int, array{role: string, content: string}>
     */
    public function prependCustomPrompt(array $messages, string $provider, string $customPrompt): array
    {
        if ($customPrompt === '') {
            return $messages;
        }

        return $this->injectPromptPrefix($messages, $provider, $customPrompt, '[Instructions: %s]');
    }

    public function supportsSystemRole(string $provider): bool
    {
        return $provider === 'anthropic' || $provider === 'openai';
    }

    /**
     * Providers with a dedicated `system` role receive the prompt as a
     * leading system message. For others the prompt is merged into the
     * first user message for maximum model compatibility.
     *
     * @param array<int, array{role: string, content: string}> $messages
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function injectPromptPrefix(array $messages, string $provider, string $prompt, string $userWrap): array
    {
        if ($this->supportsSystemRole($provider)) {
            array_unshift($messages, ['role' => 'system', 'content' => $prompt]);

            return $messages;
        }

        $wrapped = sprintf($userWrap, $prompt);
        foreach ($messages as $i => $message) {
            if (($message['role'] ?? null) === 'user') {
                $messages[$i]['content'] = $wrapped . "\n\n" . $message['content'];
                break;
            }
        }

        return $messages;
    }

    private function stringField(array $context, string $key): ?string
    {
        $value = $context[$key] ?? null;

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * @param list<string> $lines
     */
    private function appendStringLine(array &$lines, array $context, string $key, string $label): void
    {
        $value = $this->stringField($context, $key);
        if ($value === null) {
            return;
        }

        $lines[] = '- ' . $label . ': ' . $value;
    }

    /**
     * @param list<string> $lines
     */
    private function appendSizeLine(
        array &$lines,
        array $context,
        string $key,
        string $label,
        bool $includeDpr = false,
    ): void {
        $size = $context[$key] ?? null;
        if (!is_array($size) || !isset($size['width'], $size['height'])) {
            return;
        }
        if (!is_numeric($size['width']) || !is_numeric($size['height'])) {
            return;
        }

        $line = sprintf('- %s: %dx%d', $label, (int) $size['width'], (int) $size['height']);

        if ($includeDpr) {
            $dpr = isset($size['devicePixelRatio']) && is_numeric($size['devicePixelRatio'])
                ? (float) $size['devicePixelRatio']
                : 1.0;
            $line .= sprintf(' @%.2gx', $dpr);
        }

        $lines[] = $line;
    }

    /**
     * Extract debug-panel selection info from a browser URL query string.
     *
     * @return list<string>
     */
    private function parseUrlQueryContext(string $url): array
    {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!is_string($query) || $query === '') {
            return [];
        }

        parse_str($query, $params);

        $lines = [];
        if (isset($params['debugEntry']) && is_string($params['debugEntry']) && $params['debugEntry'] !== '') {
            $lines[] = '- Debug entry ID: ' . $params['debugEntry'];
        }
        if (isset($params['collector']) && is_string($params['collector']) && $params['collector'] !== '') {
            $lines[] = '- Selected collector: ' . $params['collector'];
        }

        return $lines;
    }
}

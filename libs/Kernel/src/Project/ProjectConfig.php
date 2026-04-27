<?php

declare(strict_types=1);

namespace AppDevPanel\Kernel\Project;

/**
 * Immutable project-level configuration shared across the team via VCS.
 *
 * Stores user-curated lists that should travel with the codebase: OpenAPI
 * spec URLs and external iframe URLs displayed in the panel. Meant to be
 * serialised to a JSON file (default: `<project-root>/config/adp/project.json`)
 * and committed to the repository so every developer gets the same setup.
 */
final class ProjectConfig
{
    public const int CURRENT_VERSION = 1;

    /**
     * @param array<string, string> $frames  map<displayName, url> of embedded iframes
     * @param array<string, string> $openapi map<displayName, url> of OpenAPI specs
     */
    public function __construct(
        public readonly array $frames = [],
        public readonly array $openapi = [],
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    /**
     * Build a config from an associative array (typically decoded JSON).
     *
     * Unknown keys are ignored, malformed values are dropped silently — this
     * lets the panel keep working when the on-disk file was hand-edited or
     * comes from a newer version of the schema.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            frames: self::normaliseStringMap($data['frames'] ?? []),
            openapi: self::normaliseStringMap($data['openapi'] ?? []),
        );
    }

    /**
     * @return array{version: int, frames: array<string, string>, openapi: array<string, string>}
     */
    public function toArray(): array
    {
        return [
            'version' => self::CURRENT_VERSION,
            'frames' => $this->frames,
            'openapi' => $this->openapi,
        ];
    }

    /**
     * @param array<string, string> $frames
     */
    public function withFrames(array $frames): self
    {
        return new self(self::normaliseStringMap($frames), $this->openapi);
    }

    /**
     * @param array<string, string> $openapi
     */
    public function withOpenApi(array $openapi): self
    {
        return new self($this->frames, self::normaliseStringMap($openapi));
    }

    /**
     * @param mixed $value
     * @return array<string, string>
     */
    private static function normaliseStringMap(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $result = [];
        foreach ($value as $key => $url) {
            if (!is_string($key) || !is_string($url) || $key === '' || $url === '') {
                continue;
            }
            $result[$key] = $url;
        }

        return $result;
    }
}

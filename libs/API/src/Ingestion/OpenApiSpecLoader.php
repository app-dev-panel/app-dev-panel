<?php

declare(strict_types=1);

namespace AppDevPanel\Api\Ingestion;

use RuntimeException;

/**
 * Locates and decodes `openapi/ingestion.yaml` for the `/debug/api/openapi.json` endpoint.
 *
 * The file ships next to the package root (split `app-dev-panel/api`) or lives at the
 * monorepo root; parsing uses ext-yaml when loaded and falls back to symfony/yaml.
 */
final class OpenApiSpecLoader
{
    private const string RELATIVE_PATH = '/openapi/ingestion.yaml';

    /**
     * @param list<string> $roots Directories searched in order; defaults to package root then monorepo root.
     */
    public function __construct(
        private readonly array $roots = [],
    ) {}

    /**
     * @return array<string, mixed> decoded document
     *
     * @throws RuntimeException when the file is missing or no YAML parser is available
     */
    public function load(): array
    {
        $path = $this->locate() ?? throw new RuntimeException('OpenAPI spec not found.');

        return self::parse((string) file_get_contents($path));
    }

    public function locate(): ?string
    {
        $roots = $this->roots === [] ? [dirname(__DIR__, 2), dirname(__DIR__, 4)] : $this->roots;

        foreach ($roots as $root) {
            $candidate = $root . self::RELATIVE_PATH;
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function parse(string $yaml): array
    {
        if (function_exists('yaml_parse')) {
            /** @var array<string, mixed> */
            return yaml_parse($yaml);
        }

        if (class_exists(\Symfony\Component\Yaml\Yaml::class)) {
            /** @var array<string, mixed> */
            return \Symfony\Component\Yaml\Yaml::parse($yaml);
        }

        throw new RuntimeException('Serving the OpenAPI spec requires ext-yaml or symfony/yaml.');
    }
}

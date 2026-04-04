<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorClient;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

/**
 * MCP tool that exposes application parameters and DI configuration
 * by querying the live Inspector API.
 */
final class InspectConfigTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly InspectorClient $client,
    ) {}

    public function getName(): string
    {
        return 'inspect_config';
    }

    public function getDescription(): string
    {
        return 'View application configuration: parameters, DI container config, or event listeners. Requires a running application with ADP installed.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => 'What to inspect: "params" for application parameters, "config" for DI container configuration, "events" for event listeners.',
                    'enum' => ['params', 'config', 'events'],
                    'default' => 'params',
                ],
                'group' => [
                    'type' => 'string',
                    'description' => 'Config group name for the "config" action (e.g., "di", "services"). Ignored for other actions.',
                ],
                'filter' => [
                    'type' => 'string',
                    'description' => 'Filter pattern to search within the results. Case-insensitive substring match on keys and values.',
                ],
                'service' => [
                    'type' => 'string',
                    'description' => 'Target service name for multi-app inspection. Omit for local application.',
                ],
            ],
            'additionalProperties' => false,
        ];
    }

    public function execute(array $arguments): array
    {
        $action = $arguments['action'] ?? 'params';
        $filter = $arguments['filter'] ?? null;
        $service = $arguments['service'] ?? null;

        $result = match ($action) {
            'config' => $this->fetchConfig($arguments['group'] ?? 'di', $service),
            'events' => $this->fetchEvents($service),
            default => $this->fetchParams($service),
        };

        if (!$result['success']) {
            return self::error($result['error'] ?? 'Failed to fetch configuration.');
        }

        $data = $result['data'];

        if ($data === null || $data === [] || $data === '') {
            return self::text(sprintf('No %s data available.', $action));
        }

        $output = $this->formatOutput($action, $data, $filter, $arguments['group'] ?? null);

        return self::text($output);
    }

    /**
     * @return array{success: bool, data: mixed, error: ?string}
     */
    private function fetchParams(?string $service): array
    {
        $query = $service !== null ? ['service' => $service] : [];

        return $this->client->get('/params', $query);
    }

    /**
     * @return array{success: bool, data: mixed, error: ?string}
     */
    private function fetchConfig(string $group, ?string $service): array
    {
        $query = ['group' => $group];

        if ($service !== null) {
            $query['service'] = $service;
        }

        return $this->client->get('/config', $query);
    }

    /**
     * @return array{success: bool, data: mixed, error: ?string}
     */
    private function fetchEvents(?string $service): array
    {
        $query = $service !== null ? ['service' => $service] : [];

        return $this->client->get('/events', $query);
    }

    private function formatOutput(string $action, mixed $data, ?string $filter, ?string $group): string
    {
        $title = match ($action) {
            'config' => sprintf('# Application Configuration — Group: %s', $group ?? 'di'),
            'events' => '# Event Listeners',
            default => '# Application Parameters',
        };

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (is_string($filter) && $filter !== '') {
            $json = $this->filterJsonLines($json, $filter);

            if ($json === '') {
                return sprintf("%s\n\nNo results matching filter \"%s\".", $title, $filter);
            }

            $title .= sprintf(' (filtered: "%s")', $filter);
        }

        if (strlen($json) > 15_000) {
            $json = substr($json, 0, 15_000) . "\n... (truncated, use filter to narrow results)";
        }

        return sprintf("%s\n\n```json\n%s\n```", $title, $json);
    }

    private function filterJsonLines(string $json, string $filter): string
    {
        $lower = strtolower($filter);
        $lines = explode("\n", $json);
        $matched = [];

        foreach ($lines as $line) {
            if (str_contains(strtolower($line), $lower)) {
                $matched[] = $line;
            }
        }

        return implode("\n", $matched);
    }
}

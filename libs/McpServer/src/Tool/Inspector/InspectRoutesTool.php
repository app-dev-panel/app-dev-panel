<?php

declare(strict_types=1);

namespace AppDevPanel\McpServer\Tool\Inspector;

use AppDevPanel\McpServer\Inspector\InspectorInterface;
use AppDevPanel\McpServer\Tool\ToolInterface;
use AppDevPanel\McpServer\Tool\ToolResultTrait;

/**
 * MCP tool that lists application routes and tests route matching
 * by querying the live Inspector API.
 */
final class InspectRoutesTool implements ToolInterface
{
    use ToolResultTrait;

    public function __construct(
        private readonly InspectorInterface $client,
    ) {}

    public function getName(): string
    {
        return 'inspect_routes';
    }

    public function getDescription(): string
    {
        return 'List application routes or check if a URL matches a route. Shows route names, HTTP methods, URL patterns, and middleware. Requires a running application with ADP installed.';
    }

    public function getInputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'action' => [
                    'type' => 'string',
                    'description' => '"list" to show all routes, "check" to test if a URL matches a route.',
                    'enum' => ['list', 'check'],
                    'default' => 'list',
                ],
                'path' => [
                    'type' => 'string',
                    'description' => 'URL path to check (for "check" action). Supports "METHOD /path" format, e.g., "POST /api/users". Defaults to GET if no method specified.',
                ],
                'filter' => [
                    'type' => 'string',
                    'description' => 'Filter routes by pattern, name, or method (for "list" action). Case-insensitive substring match.',
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
        $action = $arguments['action'] ?? 'list';

        return match ($action) {
            'check' => $this->checkRoute($arguments),
            default => $this->listRoutes($arguments),
        };
    }

    private function listRoutes(array $arguments): array
    {
        $filter = $arguments['filter'] ?? null;
        $service = $arguments['service'] ?? null;

        $query = $service !== null ? ['service' => $service] : [];
        $result = $this->client->get('/routes', $query);

        if (!$result['success']) {
            return self::error($result['error'] ?? 'Failed to fetch routes.');
        }

        $routes = $result['data'];

        if (!is_array($routes) || $routes === []) {
            return self::text('No routes found.');
        }

        if (is_string($filter) && $filter !== '') {
            $routes = $this->filterRoutes($routes, $filter);

            if ($routes === []) {
                return self::text(sprintf('No routes matching "%s".', $filter));
            }
        }

        return self::text($this->formatRouteList($routes, $filter));
    }

    private function checkRoute(array $arguments): array
    {
        $path = $arguments['path'] ?? null;

        if (!is_string($path) || $path === '') {
            return self::error('The "path" parameter is required for route checking. Example: "GET /api/users"');
        }

        $service = $arguments['service'] ?? null;
        $query = ['route' => $path];

        if ($service !== null) {
            $query['service'] = $service;
        }

        $result = $this->client->get('/route/check', $query);

        if (!$result['success']) {
            return self::error($result['error'] ?? 'Failed to check route.');
        }

        $data = $result['data'];

        if (!is_array($data)) {
            return self::text(sprintf('No route matches "%s".', $path));
        }

        if (($data['result'] ?? false) === false) {
            return self::text(sprintf("# Route Check: %s\n\n**Result**: No match found.", $path));
        }

        $action = $data['action'] ?? 'unknown';
        if (!is_string($action)) {
            $action = json_encode($action, JSON_UNESCAPED_SLASHES);
        }

        return self::text(sprintf("# Route Check: %s\n\n**Result**: Match found\n**Action**: `%s`", $path, $action));
    }

    private function filterRoutes(array $routes, string $filter): array
    {
        $lower = strtolower($filter);

        return array_values(array_filter($routes, static function (mixed $route) use ($lower): bool {
            if (!is_array($route)) {
                return false;
            }

            $searchable = implode(' ', [
                $route['name'] ?? '',
                $route['pattern'] ?? '',
                is_array($route['methods'] ?? null) ? implode(' ', $route['methods']) : '',
            ]);

            return str_contains(strtolower($searchable), $lower);
        }));
    }

    private function formatRouteList(array $routes, ?string $filter): string
    {
        $title = sprintf('# Application Routes (%d)', count($routes));

        if (is_string($filter) && $filter !== '') {
            $title .= sprintf(' — filtered: "%s"', $filter);
        }

        $lines = [$title, ''];
        $lines[] = '| Methods | Pattern | Name |';
        $lines[] = '|---------|---------|------|';

        foreach ($routes as $route) {
            if (!is_array($route)) {
                continue;
            }

            $methods = is_array($route['methods'] ?? null) ? implode(', ', $route['methods']) : 'ANY';
            $pattern = (string) ($route['pattern'] ?? '—');
            $name = (string) ($route['name'] ?? '—');

            $lines[] = sprintf('| %s | `%s` | %s |', $methods, $pattern, $name);
        }

        return implode("\n", $lines);
    }
}

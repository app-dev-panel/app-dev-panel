<?php

declare(strict_types=1);

namespace AppDevPanel\Api;

use AppDevPanel\Api\Debug\Controller\DebugController;
use AppDevPanel\Api\Debug\Controller\SettingsController;
use AppDevPanel\Api\Ingestion\Controller\IngestionController;
use AppDevPanel\Api\Ingestion\Controller\OtlpController;
use AppDevPanel\Api\Inspector\Controller\AuthorizationController;
use AppDevPanel\Api\Inspector\Controller\CacheController;
use AppDevPanel\Api\Inspector\Controller\CommandController;
use AppDevPanel\Api\Inspector\Controller\ComposerController;
use AppDevPanel\Api\Inspector\Controller\DatabaseController;
use AppDevPanel\Api\Inspector\Controller\FileController;
use AppDevPanel\Api\Inspector\Controller\GitController;
use AppDevPanel\Api\Inspector\Controller\InspectController;
use AppDevPanel\Api\Inspector\Controller\OpcacheController;
use AppDevPanel\Api\Inspector\Controller\RequestController;
use AppDevPanel\Api\Inspector\Controller\RoutingController;
use AppDevPanel\Api\Inspector\Controller\ServiceController;
use AppDevPanel\Api\Inspector\Controller\TaskBusController;
use AppDevPanel\Api\Inspector\Controller\TranslationController;
use AppDevPanel\Api\Llm\Controller\LlmController;
use AppDevPanel\Api\Mcp\Controller\McpController;
use AppDevPanel\Api\Mcp\Controller\McpSettingsController;
use AppDevPanel\Api\Router\Route;
use AppDevPanel\Api\Router\Router;

final class ApiRoutes
{
    /**
     * @return Route[]
     */
    public static function debugRoutes(): array
    {
        return [
            new Route('GET', '/debug/api', [DebugController::class, 'index'], 'debug/api/index'),
            new Route('GET', '/debug/api/summary/{id}', [DebugController::class, 'summary'], 'debug/api/summary'),
            new Route('GET', '/debug/api/view/{id}', [DebugController::class, 'view'], 'debug/api/view'),
            new Route('GET', '/debug/api/dump/{id}', [DebugController::class, 'dump'], 'debug/api/dump'),
            new Route(
                'GET',
                '/debug/api/object/{id}/{objectId}',
                [DebugController::class, 'object'],
                'debug/api/object',
            ),
            new Route(
                'GET',
                '/debug/api/event-stream',
                [DebugController::class, 'eventStream'],
                'debug/api/event-stream',
            ),
            new Route('GET', '/debug/api/settings', [SettingsController::class, 'index'], 'debug/api/settings'),
        ];
    }

    /**
     * @return Route[]
     */
    public static function ingestionRoutes(): array
    {
        return [
            new Route('POST', '/debug/api/ingest', [IngestionController::class, 'ingest'], 'debug/api/ingest'),
            new Route(
                'POST',
                '/debug/api/ingest/batch',
                [IngestionController::class, 'ingestBatch'],
                'debug/api/ingest/batch',
            ),
            new Route(
                'POST',
                '/debug/api/ingest/log',
                [IngestionController::class, 'ingestLog'],
                'debug/api/ingest/log',
            ),
            new Route('GET', '/debug/api/openapi.json', [IngestionController::class, 'openapi'], 'debug/api/openapi'),
            new Route(
                'POST',
                '/debug/api/otlp/v1/traces',
                [OtlpController::class, 'traces'],
                'debug/api/otlp/v1/traces',
            ),
        ];
    }

    /**
     * @return Route[]
     */
    public static function serviceRoutes(): array
    {
        return [
            new Route(
                'POST',
                '/debug/api/services/register',
                [ServiceController::class, 'register'],
                'debug/api/services/register',
            ),
            new Route(
                'POST',
                '/debug/api/services/heartbeat',
                [ServiceController::class, 'heartbeat'],
                'debug/api/services/heartbeat',
            ),
            new Route('GET', '/debug/api/services', [ServiceController::class, 'list'], 'debug/api/services/list'),
            new Route(
                'DELETE',
                '/debug/api/services/{service}',
                [ServiceController::class, 'deregister'],
                'debug/api/services/deregister',
            ),
        ];
    }

    /**
     * @return Route[]
     */
    public static function inspectorRoutes(): array
    {
        return [
            new Route('GET', '/inspect/api/events', [InspectController::class, 'eventListeners'], 'inspect/api/events'),
            new Route('GET', '/inspect/api/params', [InspectController::class, 'params'], 'inspect/api/params'),
            new Route('GET', '/inspect/api/config', [InspectController::class, 'config'], 'inspect/api/config'),
            new Route('GET', '/inspect/api/classes', [InspectController::class, 'classes'], 'inspect/api/classes'),
            new Route('GET', '/inspect/api/object', [InspectController::class, 'object'], 'inspect/api/object'),
            new Route('GET', '/inspect/api/files', [FileController::class, 'files'], 'inspect/api/files'),
            new Route('GET', '/inspect/api/routes', [RoutingController::class, 'routes'], 'inspect/api/routes'),
            new Route(
                'GET',
                '/inspect/api/route/check',
                [RoutingController::class, 'checkRoute'],
                'inspect/api/route/check',
            ),
            new Route(
                'GET',
                '/inspect/api/translations',
                [TranslationController::class, 'getTranslations'],
                'inspect/api/translations',
            ),
            new Route(
                'PUT',
                '/inspect/api/translations',
                [TranslationController::class, 'putTranslation'],
                'inspect/api/putTranslation',
            ),
            new Route('GET', '/inspect/api/table', [DatabaseController::class, 'getTables'], 'inspect/api/getTables'),
            new Route(
                'GET',
                '/inspect/api/table/{name}',
                [DatabaseController::class, 'getTable'],
                'inspect/api/getTable',
            ),
            new Route(
                'POST',
                '/inspect/api/table/explain',
                [DatabaseController::class, 'explain'],
                'inspect/api/table/explain',
            ),
            new Route(
                'POST',
                '/inspect/api/table/query',
                [DatabaseController::class, 'query'],
                'inspect/api/table/query',
            ),
            new Route('PUT', '/inspect/api/request', [RequestController::class, 'request'], 'inspect/api/request'),
            new Route(
                'POST',
                '/inspect/api/curl/build',
                [RequestController::class, 'buildCurl'],
                'inspect/api/curl/build',
            ),
            new Route('GET', '/inspect/api/git/summary', [GitController::class, 'summary'], 'inspect/api/git/summary'),
            new Route(
                'POST',
                '/inspect/api/git/checkout',
                [GitController::class, 'checkout'],
                'inspect/api/git/checkout',
            ),
            new Route('POST', '/inspect/api/git/command', [GitController::class, 'command'], 'inspect/api/git/command'),
            new Route('GET', '/inspect/api/git/log', [GitController::class, 'log'], 'inspect/api/git/log'),
            new Route('GET', '/inspect/api/phpinfo', [InspectController::class, 'phpinfo'], 'inspect/api/phpinfo'),
            new Route('GET', '/inspect/api/command', [CommandController::class, 'index'], 'inspect/api/command/index'),
            new Route('POST', '/inspect/api/command', [CommandController::class, 'run'], 'inspect/api/command/run'),
            new Route(
                'GET',
                '/inspect/api/composer',
                [ComposerController::class, 'index'],
                'inspect/api/composer/index',
            ),
            new Route(
                'GET',
                '/inspect/api/composer/inspect',
                [ComposerController::class, 'inspect'],
                'inspect/api/composer/inspect',
            ),
            new Route(
                'POST',
                '/inspect/api/composer/require',
                [ComposerController::class, 'require'],
                'inspect/api/composer/require',
            ),
            new Route('GET', '/inspect/api/cache', [CacheController::class, 'view'], 'inspect/api/cache/view'),
            new Route('DELETE', '/inspect/api/cache', [CacheController::class, 'delete'], 'inspect/api/cache/delete'),
            new Route('POST', '/inspect/api/cache/clear', [CacheController::class, 'clear'], 'inspect/api/cache/clear'),
            new Route('GET', '/inspect/api/opcache', [OpcacheController::class, 'index'], 'inspect/api/opcache/index'),
            new Route(
                'GET',
                '/inspect/api/authorization',
                [AuthorizationController::class, 'index'],
                'inspect/api/authorization/index',
            ),
            new Route(
                'POST',
                '/inspect/api/taskbus',
                [TaskBusController::class, 'handle'],
                'inspect/api/taskbus',
            ),
            new Route(
                'GET',
                '/inspect/api/taskbus/status',
                [TaskBusController::class, 'status'],
                'inspect/api/taskbus/status',
            ),
            new Route('POST', '/inspect/api/mcp', [McpController::class, 'handle'], 'inspect/api/mcp'),
            new Route(
                'GET',
                '/inspect/api/mcp/settings',
                [McpSettingsController::class, 'index'],
                'inspect/api/mcp/settings',
            ),
            new Route(
                'PUT',
                '/inspect/api/mcp/settings',
                [McpSettingsController::class, 'update'],
                'inspect/api/mcp/settings/update',
            ),
        ];
    }

    /**
     * @return Route[]
     */
    public static function llmRoutes(): array
    {
        return [
            new Route('GET', '/debug/api/llm/status', [LlmController::class, 'status'], 'debug/api/llm/status'),
            new Route('POST', '/debug/api/llm/connect', [LlmController::class, 'connect'], 'debug/api/llm/connect'),
            new Route(
                'POST',
                '/debug/api/llm/oauth/initiate',
                [LlmController::class, 'oauthInitiate'],
                'debug/api/llm/oauth/initiate',
            ),
            new Route(
                'POST',
                '/debug/api/llm/oauth/exchange',
                [LlmController::class, 'oauthExchange'],
                'debug/api/llm/oauth/exchange',
            ),
            new Route(
                'POST',
                '/debug/api/llm/disconnect',
                [LlmController::class, 'disconnect'],
                'debug/api/llm/disconnect',
            ),
            new Route('POST', '/debug/api/llm/model', [LlmController::class, 'setModel'], 'debug/api/llm/model'),
            new Route('POST', '/debug/api/llm/timeout', [LlmController::class, 'setTimeout'], 'debug/api/llm/timeout'),
            new Route(
                'POST',
                '/debug/api/llm/custom-prompt',
                [LlmController::class, 'setCustomPrompt'],
                'debug/api/llm/custom-prompt',
            ),
            new Route('GET', '/debug/api/llm/models', [LlmController::class, 'models'], 'debug/api/llm/models'),
            new Route('POST', '/debug/api/llm/chat', [LlmController::class, 'chat'], 'debug/api/llm/chat'),
            new Route('POST', '/debug/api/llm/analyze', [LlmController::class, 'analyze'], 'debug/api/llm/analyze'),
            new Route('GET', '/debug/api/llm/history', [LlmController::class, 'history'], 'debug/api/llm/history'),
            new Route(
                'POST',
                '/debug/api/llm/history',
                [LlmController::class, 'addHistory'],
                'debug/api/llm/history/add',
            ),
            new Route(
                'DELETE',
                '/debug/api/llm/history/{index}',
                [LlmController::class, 'deleteHistory'],
                'debug/api/llm/history/delete',
            ),
            new Route(
                'DELETE',
                '/debug/api/llm/history',
                [LlmController::class, 'clearHistory'],
                'debug/api/llm/history/clear',
            ),
        ];
    }

    public static function register(Router $router): void
    {
        $router->addRoutes(self::debugRoutes());
        $router->addRoutes(self::ingestionRoutes());
        $router->addRoutes(self::serviceRoutes());
        $router->addRoutes(self::inspectorRoutes());
        $router->addRoutes(self::llmRoutes());
    }
}

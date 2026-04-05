<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()->routes(
        Route::get('/')->action(Web\HomePage\Action::class)->name('home'),
        Route::get('/users')->action(Web\UsersPage\Action::class)->name('users'),
        Route::methods(['GET', 'POST'], '/contact')
            ->action(Web\ContactPage\Action::class)
            ->name('contact'),
        Route::get('/api-playground')->action(Web\ApiPlaygroundPage\Action::class)->name('api-playground'),
        Route::get('/error')->action(Web\ErrorPage\Action::class)->name('error-demo'),
        Route::methods(['GET', 'POST'], '/log-demo')
            ->action(Web\LogDemoPage\Action::class)
            ->name('log-demo'),
        Route::methods(['GET', 'POST'], '/var-dumper')
            ->action(Web\VarDumperPage\Action::class)
            ->name('var-dumper'),
    ),
    Group::create('/api')
        ->routes(
            Route::get('/')->action(Web\Api\IndexAction::class)->name('api-index'),
            Route::get('/users')->action(Web\Api\UsersAction::class)->name('api-users'),
            Route::get('/error')->action(Web\Api\ErrorAction::class)->name('api-error'),
            Route::get('/openapi.json')->action(Web\Api\OpenApiAction::class)->name('api-openapi'),
        )
        ->prependMiddleware(FormatDataResponseAsJson::class),
    Group::create('/test/fixtures')
        ->routes(
            Route::get('/logs')->action(Web\TestFixtures\LogsAction::class)->name('test-logs'),
            Route::get('/logs-context')->action(Web\TestFixtures\LogsContextAction::class)->name('test-logs-context'),
            Route::get('/events')->action(Web\TestFixtures\EventsAction::class)->name('test-events'),
            Route::get('/dump')->action(Web\TestFixtures\DumpAction::class)->name('test-dump'),
            Route::get('/timeline')->action(Web\TestFixtures\TimelineAction::class)->name('test-timeline'),
            Route::get('/request-info')->action(Web\TestFixtures\RequestInfoAction::class)->name('test-request-info'),
            Route::get('/exception')->action(Web\TestFixtures\ExceptionAction::class)->name('test-exception'),
            Route::get('/exception-chained')->action(Web\TestFixtures\ExceptionChainedAction::class)->name(
                'test-exception-chained',
            ),
            Route::get('/multi')->action(Web\TestFixtures\MultiAction::class)->name('test-multi'),
            Route::get('/logs-heavy')->action(Web\TestFixtures\LogsHeavyAction::class)->name('test-logs-heavy'),
            Route::get('/http-client')->action(Web\TestFixtures\HttpClientAction::class)->name('test-http-client'),
            Route::get('/filesystem')->action(Web\TestFixtures\FilesystemAction::class)->name('test-filesystem'),
            Route::get('/filesystem-streams')->action(Web\TestFixtures\FileStreamAction::class)->name(
                'test-filesystem-streams',
            ),
            Route::get('/database')->action(Web\TestFixtures\DatabaseAction::class)->name('test-database'),
            Route::get('/mailer')->action(Web\TestFixtures\MailerAction::class)->name('test-mailer'),
            Route::get('/queue')->action(Web\TestFixtures\QueueAction::class)->name('test-queue'),
            Route::get('/validator')->action(Web\TestFixtures\ValidatorAction::class)->name('test-validator'),
            Route::get('/router')->action(Web\TestFixtures\RouterAction::class)->name('test_router'),
            Route::get('/cache')->action(Web\TestFixtures\CacheAction::class)->name('test-cache'),
            Route::get('/cache-heavy')->action(Web\TestFixtures\CacheHeavyAction::class)->name('test-cache-heavy'),
            Route::get('/opentelemetry')->action(Web\TestFixtures\OpenTelemetryAction::class)->name(
                'test-opentelemetry',
            ),
            Route::get('/translator')->action(Web\TestFixtures\TranslatorAction::class)->name('test-translator'),
            Route::get('/security')->action(Web\TestFixtures\SecurityAction::class)->name('test-security'),
            Route::get('/elasticsearch')->action(Web\TestFixtures\ElasticsearchAction::class)->name(
                'test-elasticsearch',
            ),
            Route::get('/redis')->action(Web\TestFixtures\RedisAction::class)->name('test-redis'),
            Route::get('/coverage')->action(Web\TestFixtures\CoverageAction::class)->name('test-coverage'),
            Route::get('/view')->action(Web\TestFixtures\ViewAction::class)->name('test-view'),
            Route::get('/template')->action(Web\TestFixtures\TemplateAction::class)->name('test-template'),
            Route::get('/assets')->action(Web\TestFixtures\AssetAction::class)->name('test-assets'),
            Route::methods(['GET', 'POST'], '/reset')
                ->action(Web\TestFixtures\ResetAction::class)
                ->name('test-reset'),
            Route::methods(['GET', 'POST'], '/reset-cli')
                ->action(Web\TestFixtures\ResetCliAction::class)
                ->name('test-reset-cli'),
        )
        ->prependMiddleware(FormatDataResponseAsJson::class),
];

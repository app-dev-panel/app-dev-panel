<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()->routes(Route::get('/')->action(Web\HomePage\Action::class)->name('home')),
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
            Route::get('/messenger')->action(Web\TestFixtures\MessengerAction::class)->name('test-messenger'),
            Route::get('/validator')->action(Web\TestFixtures\ValidatorAction::class)->name('test-validator'),
            Route::get('/router')->action(Web\TestFixtures\RouterAction::class)->name('test-router'),
            Route::get('/cache')->action(Web\TestFixtures\CacheAction::class)->name('test-cache'),
            Route::get('/cache-heavy')->action(Web\TestFixtures\CacheHeavyAction::class)->name('test-cache-heavy'),
            Route::get('/opentelemetry')->action(Web\TestFixtures\OpenTelemetryAction::class)->name(
                'test-opentelemetry',
            ),
            Route::methods(['GET', 'POST'], '/reset')
                ->action(Web\TestFixtures\ResetAction::class)
                ->name('test-reset'),
            Route::methods(['GET', 'POST'], '/reset-cli')
                ->action(Web\TestFixtures\ResetCliAction::class)
                ->name('test-reset-cli'),
        )
        ->prependMiddleware(FormatDataResponseAsJson::class),
];

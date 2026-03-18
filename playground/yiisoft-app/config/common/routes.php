<?php

declare(strict_types=1);

use App\Web;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

return [
    Group::create()->routes(Route::get('/')->action(Web\HomePage\Action::class)->name('home')),
    Group::create('/test/scenarios')->routes(
        Route::get('/logs')->action(Web\TestScenarios\LogsAction::class)->name('test-logs'),
        Route::get('/logs-context')->action(Web\TestScenarios\LogsContextAction::class)->name('test-logs-context'),
        Route::get('/events')->action(Web\TestScenarios\EventsAction::class)->name('test-events'),
        Route::get('/dump')->action(Web\TestScenarios\DumpAction::class)->name('test-dump'),
        Route::get('/timeline')->action(Web\TestScenarios\TimelineAction::class)->name('test-timeline'),
        Route::get('/request-info')->action(Web\TestScenarios\RequestInfoAction::class)->name('test-request-info'),
        Route::get('/exception')->action(Web\TestScenarios\ExceptionAction::class)->name('test-exception'),
        Route::get('/exception-chained')->action(Web\TestScenarios\ExceptionChainedAction::class)->name(
            'test-exception-chained',
        ),
        Route::get('/multi')->action(Web\TestScenarios\MultiAction::class)->name('test-multi'),
        Route::get('/logs-heavy')->action(Web\TestScenarios\LogsHeavyAction::class)->name('test-logs-heavy'),
        Route::get('/http-client')->action(Web\TestScenarios\HttpClientAction::class)->name('test-http-client'),
        Route::get('/filesystem')->action(Web\TestScenarios\FilesystemAction::class)->name('test-filesystem'),
    ),
];

<?php

declare(strict_types=1);

use App\Http\Controllers\HomeController;
use App\Http\Controllers\OpenApiController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\TestFixtures\AssetAction;
use App\Http\Controllers\TestFixtures\CacheAction;
use App\Http\Controllers\TestFixtures\CacheHeavyAction;
use App\Http\Controllers\TestFixtures\CoverageAction;
use App\Http\Controllers\TestFixtures\DatabaseAction;
use App\Http\Controllers\TestFixtures\DumpAction;
use App\Http\Controllers\TestFixtures\ElasticsearchAction;
use App\Http\Controllers\TestFixtures\EventsAction;
use App\Http\Controllers\TestFixtures\ExceptionAction;
use App\Http\Controllers\TestFixtures\ExceptionChainedAction;
use App\Http\Controllers\TestFixtures\FileStreamAction;
use App\Http\Controllers\TestFixtures\FilesystemAction;
use App\Http\Controllers\TestFixtures\HttpClientAction;
use App\Http\Controllers\TestFixtures\LogsAction;
use App\Http\Controllers\TestFixtures\LogsContextAction;
use App\Http\Controllers\TestFixtures\LogsHeavyAction;
use App\Http\Controllers\TestFixtures\MailerAction;
use App\Http\Controllers\TestFixtures\MultiAction;
use App\Http\Controllers\TestFixtures\OpenTelemetryAction;
use App\Http\Controllers\TestFixtures\QueueAction;
use App\Http\Controllers\TestFixtures\RedisAction;
use App\Http\Controllers\TestFixtures\RequestInfoAction;
use App\Http\Controllers\TestFixtures\ResetAction;
use App\Http\Controllers\TestFixtures\ResetCliAction;
use App\Http\Controllers\TestFixtures\RouterAction;
use App\Http\Controllers\TestFixtures\SecurityAction;
use App\Http\Controllers\TestFixtures\TemplateAction;
use App\Http\Controllers\TestFixtures\TimelineAction;
use App\Http\Controllers\TestFixtures\TranslatorAction;
use App\Http\Controllers\TestFixtures\ValidatorAction;
use App\Http\Controllers\TestFixtures\ViewAction;
use Illuminate\Support\Facades\Route;

// Web pages
Route::get('/', [PageController::class, 'home']);
Route::get('/users', [PageController::class, 'users']);
Route::match(['GET', 'POST'], '/contact', [PageController::class, 'contact']);
Route::get('/api-playground', [PageController::class, 'apiPlayground']);
Route::get('/error', [PageController::class, 'errorDemo']);
Route::match(['GET', 'POST'], '/log-demo', [PageController::class, 'logDemo']);
Route::match(['GET', 'POST'], '/var-dumper', [PageController::class, 'varDumper']);

// API
Route::get('/api', [HomeController::class, 'index']);
Route::get('/api/users', [HomeController::class, 'users']);
Route::get('/api/error', [HomeController::class, 'error']);
Route::get('/api/openapi.json', OpenApiController::class);

// Test fixtures
Route::prefix('test/fixtures')->group(function (): void {
    Route::match(['GET', 'POST'], '/reset', ResetAction::class);
    Route::match(['GET', 'POST'], '/reset-cli', ResetCliAction::class);
    Route::get('/logs', LogsAction::class);
    Route::get('/logs-context', LogsContextAction::class);
    Route::get('/logs-heavy', LogsHeavyAction::class);
    Route::get('/events', EventsAction::class);
    Route::get('/dump', DumpAction::class);
    Route::get('/timeline', TimelineAction::class);
    Route::get('/exception', ExceptionAction::class);
    Route::get('/exception-chained', ExceptionChainedAction::class);
    Route::get('/request-info', RequestInfoAction::class);
    Route::get('/multi', MultiAction::class);
    Route::get('/cache', CacheAction::class);
    Route::get('/cache-heavy', CacheHeavyAction::class);
    Route::get('/database', DatabaseAction::class);
    Route::get('/http-client', HttpClientAction::class);
    Route::get('/mailer', MailerAction::class);
    Route::get('/queue', QueueAction::class);
    Route::get('/validator', ValidatorAction::class);
    Route::get('/router', RouterAction::class)->name('test_router');
    Route::get('/filesystem', FilesystemAction::class);
    Route::get('/filesystem-streams', FileStreamAction::class);
    Route::get('/opentelemetry', OpenTelemetryAction::class);
    Route::get('/translator', TranslatorAction::class);
    Route::get('/security', SecurityAction::class);
    Route::get('/elasticsearch', ElasticsearchAction::class);
    Route::get('/redis', RedisAction::class);
    Route::get('/coverage', CoverageAction::class);
    Route::get('/view', ViewAction::class);
    Route::get('/template', TemplateAction::class);
    Route::get('/assets', AssetAction::class);
});

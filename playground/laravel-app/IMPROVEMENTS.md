# Laravel Playground — Improvement Report

## Overview

**Framework:** Laravel 12 | **Port:** 8104 | **Fixtures:** 21 actions | **Adapter:** `adapter-laravel`

## Strengths

- **Auto-discovery** — adapter auto-registers via `extra.laravel.providers`, zero config needed
- **Best event integration** — 6 dedicated event listeners (Database, Cache, Mail, Queue, HttpClient, Console) capture data via Laravel's event system
- **PSR proxy coverage** — `LoggerInterface` and `ClientInterface` proxied via `$app->extend()`
- **Laravel-native events** — `EventsAction` and `MultiAction` use `Illuminate\Contracts\Events\Dispatcher`, captured by `LaravelEventDispatcherProxy`
- **Full fixture set** — 21 actions covering all required and optional fixtures

## Weaknesses

- **7 fixtures use direct collector injection** — most direct injection of any playground (cache, database, mailer, queue, validator, router, var-dumper)
- **No real database queries** — `DatabaseAction` calls `DatabaseCollector::logQuery()` directly; despite having SQLite configured and `QueryExecuted` event listener wired
- **No real cache operations** — `CacheAction` calls `CacheCollector::logCacheOperation()` directly; despite having `CacheListener` wired for cache events
- **No real mail sending** — `MailerAction` calls `MailerCollector::collectMessage()` directly; despite having `MailListener` for `MessageSent` events
- **No real queue dispatch** — `QueueAction` calls `QueueCollector::logMessage()` directly
- **Newest adapter** — less battle-tested than Yii 3/Symfony, 55 unit tests but no integration tests
- **Dump fixture** — uses direct `VarDumperCollector` injection instead of `dump()` function
- **Missing exception fixture** — `/test/fixtures/exception` throws but returns 500 (expected), however the debug entry may not be stored if exception kills the process before `terminate()`

## Fixture Data Capture Analysis

| Method | Count | Actions |
|--------|------:|---------|
| PSR proxy | 7 | logs, logs-context, logs-heavy, http-client, timeline, multi (logger part) |
| Framework event | 2 | events, multi (event part) |
| Direct collector | 7 | cache, cache-heavy, database, mailer, queue, validator, router |
| Native (exception/file) | 4 | exception, exception-chained, filesystem, reset-cli |
| Direct storage | 1 | reset |
| None needed | 1 | request-info |

## Path to Improvement

### High Priority

1. **Database fixture via real queries** — SQLite is already configured; change `DatabaseAction` to run actual queries (`DB::select('SELECT 1')`, `DB::table('test')->get()`) and let `QueryExecuted` event + `DatabaseListener` capture them naturally
2. **Cache fixture via real cache** — array cache driver is configured; change `CacheAction` to use `Cache::put()`, `Cache::get()`, `Cache::forget()` and let `CacheHit`/`CacheMissed`/`KeyWritten`/`KeyForgotten` events + `CacheListener` capture them
3. **Dump fixture via `dump()`** — change `DumpAction` to call `dump()` (Symfony VarDumper), let the `VarDumper::setHandler()` in `DebugMiddleware` capture it
4. **Mail fixture via real Mailer** — configure `log` or `array` mail transport; send real `Mailable`, let `MessageSent` event + `MailListener` capture it

### Medium Priority

5. **Queue fixture via real dispatch** — configure sync queue driver; dispatch a real job, let `JobProcessing`/`JobProcessed` events + `QueueListener` capture it
6. **Add integration tests** — test full `AppDevPanelServiceProvider` boot with orchestra/testbench, verify all collectors are registered and events are wired
7. **Exception handling** — verify `ExceptionCollector` actually captures exceptions; the `DebugMiddleware` catches, re-throws, but `terminate()` may not run

### Low Priority

8. **Add router:auto fixture** — `FixtureRegistry` defines it but no playground implements it
9. **Add Validator fixture via real Validator** — use `Validator::make()` with real rules instead of `ValidatorCollector` direct calls
10. **Router fixture via real routing** — instead of `RouterCollector` direct injection, let `RouterDataExtractor` in `DebugMiddleware` capture the matched route naturally

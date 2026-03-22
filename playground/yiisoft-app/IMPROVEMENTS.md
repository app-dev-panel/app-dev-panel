# Yiisoft Playground — Improvement Report

## Overview

**Framework:** Yii 3 | **Port:** 8101 | **Fixtures:** 22 actions | **Adapter:** `adapter-yiisoft`

## Strengths

- **Most complete playground** — 22 fixture actions covering all 23 registry fixtures
- **Best PSR integration** — uses `LoggerInterface`, `EventDispatcherInterface`, `ClientInterface` via PSR proxies (8 actions)
- **Clean architecture** — PSR-15 request handlers (one class per action), DI via constructor injection
- **Auto-registration** — `yiisoft/config` plugin system, zero manual wiring needed
- **Mature adapter** — first adapter developed, most battle-tested

## Weaknesses

- **6 fixtures use direct collector injection** — `CacheAction`, `DatabaseAction`, `MailerAction`, `ValidatorAction`, `RouterAction`, `MessengerAction` inject collectors directly instead of using framework APIs
- **No real database** — `DatabaseAction` calls `DatabaseCollector::logQuery()` directly with fake SQL
- **No real mailer** — `MailerAction` calls `MailerCollector::collectMessage()` directly
- **No real queue** — `MessengerAction` calls `QueueCollector::logMessage()` directly
- **Missing `.gitignore`** — runtime/vendor not ignored

## Fixture Data Capture Analysis

| Method | Count | Actions |
|--------|------:|---------|
| PSR proxy | 8 | logs, logs-context, logs-heavy, events, http-client, multi, timeline, dump |
| Direct collector | 6 | cache, cache-heavy, database, mailer, messenger, validator, router |
| Native (exception/file) | 5 | exception, exception-chained, filesystem, reset, reset-cli |
| None needed | 1 | request-info |

## Path to Improvement

1. **Database fixture via real DB** — configure SQLite, run actual queries via `yiisoft/db`, let the DB profiler capture queries naturally instead of `logQuery()` directly
2. **Cache fixture via real cache** — use `yiisoft/cache` with array/file driver, let cache proxy intercept operations
3. **Mailer fixture via real mailer** — use `yiisoft/mailer` with file transport, let mailer proxy capture messages
4. **Queue fixture via real queue** — use `yiisoft/queue` with sync driver if available
5. **Add `.gitignore`** — ignore `runtime/`, `vendor/`, `config/packages/`
6. **Add `TestFixtureEvent`** — currently uses a dedicated event class, verify it's properly captured by EventCollector
7. **Add router:auto fixture** — `FixtureRegistry` defines it but no playground implements it

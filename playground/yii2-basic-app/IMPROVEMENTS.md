# Yii 2 Playground — Improvement Report

## Overview

**Framework:** Yii 2 | **Port:** 8103 | **Fixtures:** 18 actions | **Adapter:** `adapter-yii2`

## Strengths

- **Framework-native data capture** — uses Yii 2 static methods (`\Yii::info()`, `Event::trigger()`) which are the idiomatic way in Yii 2; adapter's DB profiling and event hooks intercept them
- **Auto-bootstrap** — adapter registers via `BootstrapInterface` + `extra.bootstrap` in composer, no manual module config needed
- **Real DB profiling** — `yii\db\Connection` events are hooked by the adapter to capture actual SQL queries
- **Mature adapter** — 95 tests, stable

## Weaknesses

- **Fewest fixtures** — only 18 actions; missing `RouterAction`, `ValidatorAction` (these collectors are not supported in Yii 2)
- **No PSR proxy usage** — only 1 action uses PSR injection (`HttpClientAction` uses `ClientInterface`); everything else goes through Yii 2 statics
- **Static coupling** — `\Yii::info()`, `Event::trigger()`, `\Yii::$app->getModule()` — hard to test, tightly coupled to framework globals
- **Direct collector injection via Module** — `CacheAction`, `DatabaseAction`, `MailerAction`, `DumpAction` access collectors via `\Yii::$app->getModule('adp')->getCollector()`
- **Legacy architecture** — Yii 2's service locator pattern (no real DI container) limits clean dependency injection
- **No queue support** — Yii 2 has no built-in queue; adapter doesn't integrate with `yii2-queue` extension

## Fixture Data Capture Analysis

| Method | Count | Actions |
|--------|------:|---------|
| Framework static | 6 | logs, logs-context, logs-heavy, events, multi, timeline |
| Direct collector (via Module) | 4 | cache, cache-heavy, database, mailer |
| PSR proxy | 1 | http-client |
| Direct DI injection | 1 | dump |
| Native (exception/file) | 4 | exception, exception-chained, filesystem, reset-cli |
| Direct storage | 1 | reset |
| None needed | 1 | request-info |

## Missing Fixtures (vs other playgrounds)

| Fixture | Reason |
|---------|--------|
| `queue:basic` | Yii 2 has no built-in queue component |
| `router:basic` | `RouterCollector` not wired in Yii 2 adapter |
| `validator:basic` | `ValidatorCollector` not wired in Yii 2 adapter |

## Path to Improvement

### High Priority

1. **Add RouterCollector support** — Yii 2's `UrlManager` has route rules accessible via `$app->urlManager->rules`; create a `Yii2RouterDataExtractor` that populates `RouterCollector` during `EVENT_BEFORE_ACTION`
2. **Add ValidatorCollector support** — hook into `yii\base\Model::validate()` via behavior or event to capture validation rules and results
3. **Database fixture via real queries** — instead of `DatabaseCollector::logQuery()` directly, run actual `\Yii::$app->db->createCommand('SELECT 1')->queryAll()` and let the DB profiler capture it

### Medium Priority

4. **Cache fixture via real cache** — use `\Yii::$app->cache->set()`, `get()`, `delete()` with array cache component, and wire `CacheCollector` to cache events
5. **Mail fixture via real mailer** — use `\Yii::$app->mailer->compose()->send()` with file transport, hook `EVENT_AFTER_SEND` to capture messages naturally
6. **Add QueueAction** — if `yii2-queue` extension is available, integrate `QueueCollector` with its events

### Low Priority

7. **Modernize DI** — where possible, use `\Yii::$container->get()` instead of `\Yii::$app->getModule()` for collector access
8. **Add .gitignore entries** — ensure `runtime/`, `vendor/`, `web/assets/` are ignored
9. **Add router:auto fixture** — `FixtureRegistry` defines it but no playground implements it

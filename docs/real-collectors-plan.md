# Plan: Replace Fake Fixtures with Real Collectors

## Goal

Playground fixtures currently feed hardcoded data directly into collectors
(`$collector->logRequest(...)`, `$collector->logCommand(...)`) instead of
exercising real integrations. This masks missing proxy/adapter code and gives
no proof the collector works against a live client.

Target state: every fixture triggers real work (PSR interface, framework
component, or real driver); the adapter intercepts it transparently via a
proxy, event listener, or decorator — the same pattern already used for
DB/HTTP/Logger/Mailer/Events.

## Inventory of Fake Fixtures

Source for the list: `playground/*/TestFixtures/` direct calls to
`*Collector::log*()` / `*Collector::collect*()`.

### Yii3 (`playground/yii3-app/src/Web/TestFixtures/`)
| Fixture | Direct call | Why it is fake |
|---|---|---|
| `CacheAction.php:27-59` | `CacheCollector::logCacheOperation` | No PSR-16 proxy in adapter |
| `CacheHeavyAction.php:48-56` | `CacheCollector::logCacheOperation` ×100 | Same |
| `ElasticsearchAction.php:24-60` | `ElasticsearchCollector::logRequest` | No ES client proxy |
| `OpenTelemetryAction.php:27-99` | `OpenTelemetryCollector::collect` | No OTel SDK integration |
| `QueueAction.php:23-48` | `QueueCollector::logMessage` | No `yiisoft/queue` interceptor |
| `RedisAction.php:24-76` | `RedisCollector::logCommand` | No Redis client proxy |
| `SecurityAction.php:22-46` | `AuthorizationCollector::collectUser` + `logAccessDecision` | No `yiisoft/auth` decorator |

### Yii2 (`playground/yii2-basic-app/src/actions/testFixtures/`)
| Fixture | Direct call | Why it is fake |
|---|---|---|
| `CacheAction.php:25-54` | `CacheCollector::logCacheOperation` | No `yii\caching\Cache` proxy |
| `ElasticsearchAction.php:30-66` | `ElasticsearchCollector::logRequest` | No ES client proxy |
| `QueueAction.php:25-50` | `QueueCollector::logMessage` | `yiisoft/yii2-queue` not installed |
| `RedisAction.php:26-78` | `RedisCollector::logCommand` | No Redis client proxy |

### Laravel (`playground/laravel-app/app/Http/Controllers/TestFixtures/`)
| Fixture | Direct call | Why it is fake |
|---|---|---|
| `ElasticsearchAction.php:20-56` | `ElasticsearchCollector::logRequest` | No ES client proxy |
| `RedisAction.php:24-71` | `RedisCollector::logCommand` | No Redis proxy (see `RedisAction.php:12-14` comment) |

### Symfony (`playground/symfony-app/src/Controller/TestFixtures/`)
| Fixture | Direct call | Why it is fake |
|---|---|---|
| `ElasticsearchAction.php:28-64` | `ElasticsearchCollector::logRequest` | No ES client proxy |
| `RedisAction.php:27-74` | `RedisCollector::logCommand` | No Redis proxy (see `RedisAction.php:13-16` comment) |

## What Is Already Intercepted Properly (reference patterns)

- **Yii2 Mailer** — `Event::on(BaseMailer::class, EVENT_AFTER_SEND)` in
  `libs/Adapter/Yii2/src/Module.php:1080-1100`.
- **Yii2 DB profiling** — Yii log target in
  `libs/Adapter/Yii2/src/Collector/DbProfilingTarget.php:44-62`.
- **Yii2 View/Assets** — `Event::on(View::class, EVENT_BEFORE_RENDER / EVENT_END_PAGE)` in `Module.php:1239-1248` and `1156+`.
- **Symfony cache pool** — real `CacheItemPoolInterface` decorator (playground `CacheAction.php` uses `$this->cache->getItem/save/deleteItem`).
- **Symfony messenger** — real `MessageBusInterface::dispatch` with middleware.
- **Laravel cache/queue** — real `Cache::*` facade and `Queue::dispatchSync`.

## Tasks

### Ready to take now

#### T1. Yii2 CacheCollector — real proxy
- **Status**: actionable immediately.
- **Approach**: add `Adp\Adapter\Yii2\Proxy\CacheProxy extends yii\caching\Cache` (or decorator around `yii\caching\CacheInterface`). Wrap `get/set/delete/exists/multiGet/multiSet/flush` and feed `CacheOperationRecord(pool, operation, key, hit, duration, value)` to `CacheCollector::logCacheOperation`.
- **Wiring**: register in `Module.php` by replacing the `cache` component in DI (Yii2 allows `Yii::$app->set('cache', $proxy)` during bootstrap, wrapping whatever the user configured).
- **Playground**: add a `cache` component to `playground/yii2-basic-app/config/web.php` (`yii\caching\FileCache`); rewrite `CacheAction.php` to use `Yii::$app->cache->set/get/delete`.
- **Collector**: `libs/Kernel/src/Collector/CacheCollector.php:26-43` (no changes needed).
- **Risk**: low. Yii2 supports replacing components at bootstrap; no user-code change required.

#### T2. Yii2 QueueCollector — real integration
- **Status**: actionable immediately, but requires a dependency.
- **Approach**: add `yiisoft/yii2-queue` (any driver, e.g. `sync` or `db`) to `playground/yii2-basic-app/composer.json`. Hook `Queue::EVENT_BEFORE_PUSH`, `EVENT_AFTER_PUSH`, `EVENT_BEFORE_EXEC`, `EVENT_AFTER_EXEC`, `EVENT_AFTER_ERROR` in `Module.php` (same pattern as Mailer). Map each event to `MessageRecord(messageClass, bus, transport, dispatched, handled, failed, duration, message)`.
- **Playground**: configure `queue` component (sync driver to avoid worker setup); rewrite `QueueAction.php` to push a `yii\base\BaseObject` job.
- **Collector**: `libs/Kernel/src/Collector/QueueCollector.php:78-87` (no changes).
- **Risk**: medium. Installing a new dep in a playground only; no core changes.

#### T3. Yii3 CacheCollector — real PSR-16 decorator
- **Status**: actionable immediately. Yii3 cache is plain PSR-16 (`Psr\SimpleCache\CacheInterface`) via `yiisoft/cache` — no framework-specific interface, so a PSR-16 decorator is the entire implementation. Already noted in `playground/yii3-app/IMPROVEMENTS.md:35`.
- **Approach**: implement `CacheProxy implements Psr\SimpleCache\CacheInterface` wrapping an inner `CacheInterface`. Wrap `get/set/delete/has/getMultiple/setMultiple/deleteMultiple/clear`; feed `CacheOperationRecord(pool, operation, key, hit, duration, value)` to `CacheCollector::logCacheOperation`. PSR-6 (`Psr\Cache\CacheItemPoolInterface`) decorator can follow the same shape if a user app binds it.
- **Location**: put the decorator in `libs/Kernel/src/Proxy/` (framework-neutral, PSR) so Laravel/Symfony can reuse. Wire via Yii3 DI in `libs/Adapter/Yii3/config/` — override the `Psr\SimpleCache\CacheInterface` binding to return `new CacheProxy($inner, $collector)`.
- **Playground**: add `yiisoft/cache` (array driver for speed) to `playground/yii3-app/composer.json`; rewrite `CacheAction.php` / `CacheHeavyAction.php` to inject `Psr\SimpleCache\CacheInterface` and call `->set/get/delete/has`.
- **Collector**: `libs/Kernel/src/Collector/CacheCollector.php:26-43` (no changes).
- **Risk**: low. Pure PSR — no framework coupling.

### Investigate / design first

#### T4. Yii3 Authorization (Security)
- `yiisoft/auth` / `yiisoft/access` / `yiisoft/user` — decorate `AccessCheckerInterface` and `CurrentUser`. Fire `AuthorizationCollector::collectUser` + `logAccessDecision` from the decorator.
- Rewrite `SecurityAction.php` to call `AccessCheckerInterface::userHasPermission`.

#### T5. Yii3 Queue
- `yiisoft/queue` has `MessageHandlerInterface` + middleware pipeline. Add middleware that measures dispatch/exec duration and feeds `QueueCollector::logMessage`.
- Rewrite `QueueAction.php` to push via `QueueInterface`.

#### T6. Yii3 OpenTelemetry
- Integrate `open-telemetry/sdk` with a custom `SpanProcessorInterface` that forwards to `OpenTelemetryCollector::collect`.
- Playground already uses Yii3 DI → bind the SDK + processor; rewrite `OpenTelemetryAction.php` to produce spans via the SDK.

### Cross-framework, no PSR interface

#### T7. Redis collector — all 4 playgrounds
- No standard PHP interface. Options:
  1. Proxy around `Predis\ClientInterface` (most common).
  2. Proxy around `Redis` (phpredis extension) — harder, ext class.
- Plan: implement a `PredisProxy` in `libs/Kernel/src/Proxy/` (framework-neutral), wire per adapter. Fixtures get a real `Predis\Client` and call `set/get/del/...`.
- Requires a Redis server in CI or an in-memory predis fake; pick one.

#### T8. Elasticsearch collector — all 4 playgrounds
- No PSR interface. `elasticsearch/elasticsearch` SDK uses PSR-18 `ClientInterface` under the hood → a PSR-18 decorator covers HTTP traffic but loses query structure.
- Plan: either decorate `Elastic\Elasticsearch\Client` (public API: `search/index/delete/bulk/...`) or wrap the transport layer. Decision needed before implementation.
- CI/runtime: require a live ES container or skip the fixture when unavailable.

## Suggested Order

1. **T1 — Yii2 Cache** (smallest scope, self-contained, proves the pattern).
2. **T2 — Yii2 Queue** (adds one dep, uses existing event pattern).
3. **T3 — Yii3 Cache** (PSR-16 proxy — reusable design for T7/T8 choices).
4. **T4 / T5 — Yii3 Authorization / Queue**.
5. **T6 — Yii3 OpenTelemetry**.
6. **T7 / T8 — Redis and Elasticsearch** (blocked on design decision and on CI infra for the backing service).

## Definition of Done (per task)

- Fixture calls real framework/PSR API (no direct `$collector->log*` in fixture code).
- Adapter intercepts via proxy / event / middleware / decorator.
- `make test` green; relevant unit tests added in `libs/Adapter/*/tests/`.
- E2E fixture scenario in `libs/Testing/` triggers the collector and asserts captured data.
- `make modulite` and `make mago` clean.

## Out of Scope

- Adding real Redis/ES services to CI (tracked as part of T7/T8, decide later).
- Frontend changes (collectors and UI already exist).

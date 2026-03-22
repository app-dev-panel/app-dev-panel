# Symfony Playground — Improvement Report

## Overview

**Framework:** Symfony 7 | **Port:** 8102 | **Fixtures:** 22 actions | **Adapter:** `adapter-symfony`

## Strengths

- **Full fixture coverage** — 22 actions, matches Yiisoft as the most complete
- **Best PSR proxy usage** — 9 actions use PSR proxies (`LoggerInterface`, `EventDispatcherInterface`, `ClientInterface`)
- **Proper Symfony integration** — Bundle registration, YAML config, route attributes, Symfony conventions
- **Strong test suite** — adapter has 150 tests (98.9% coverage), best-tested adapter
- **Native VarDumper** — uses Symfony's `dump()` function, captured by VarDumper handler
- **Mago clean** — only 11 baselined issues

## Weaknesses

- **6 fixtures use direct collector injection** — same as Yiisoft: cache, database, mailer, validator, router, messenger inject collectors directly
- **No real database** — `DatabaseAction` calls `DatabaseCollector::logQuery()` with fake data, not Doctrine DBAL
- **No real cache** — `CacheAction` calls `CacheCollector::logCacheOperation()` directly, not Symfony Cache component
- **No real mailer** — `MailerAction` calls `MailerCollector::collectMessage()` directly, not Symfony Mailer
- **No real messenger** — `MessengerAction` calls `QueueCollector::logMessage()` directly, not Symfony Messenger
- **Manual bundle registration** — requires editing `bundles.php`, `routes/`, and `config/packages/` manually

## Fixture Data Capture Analysis

| Method | Count | Actions |
|--------|------:|---------|
| PSR proxy | 9 | logs, logs-context, logs-heavy, events, http-client, multi, timeline, dump |
| Direct collector | 6 | cache, cache-heavy, database, mailer, messenger, validator, router |
| Native (exception/file) | 5 | exception, exception-chained, filesystem, reset, reset-cli |
| None needed | 1 | request-info |

## Path to Improvement

1. **Database fixture via Doctrine DBAL** — add `doctrine/dbal` + SQLite, run actual queries, let `DatabaseSubscriber` capture queries via `doctrine.dbal.logging`
2. **Cache fixture via Symfony Cache** — configure `framework.cache` with array adapter, use `CacheInterface`, let cache event subscriber capture operations
3. **Mailer fixture via Symfony Mailer** — configure null transport, send real `Email` objects, let mailer subscriber capture
4. **Messenger fixture via Symfony Messenger** — configure sync transport, dispatch real messages, let messenger subscriber capture
5. **Add Flex recipe** — create a Symfony Flex recipe for automatic config file generation during `composer require`
6. **Add router:auto fixture** — `FixtureRegistry` defines it but no playground implements it
7. **Reduce manual setup** — document or automate the 3 config files (`bundles.php`, route import, `app_dev_panel.yaml`) needed for installation

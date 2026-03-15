# Variant E: Dashboard Grid — Inspector Dashboard

## Overview

The Inspector dashboard provides a live view of the application's structural configuration: routes,
DI container bindings, parameters, middleware stack, event listeners, and console commands. Unlike
the Debug dashboard (which shows per-request data), Inspector shows the current application state.

## Default Inspector Layout

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/users ▾  #a3f7c1  2026-03-15 14:32:07          200 OK 145ms         + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐ ┌───────────┐ ┌───────────┐                                                       │
│  │ Debug   │ │ Inspector │ │ Perf      │ │ Custom 1  │  +                                                     │
│  │         │ │ ▀▀▀▀▀▀▀▀▀ │ │           │ │           │                                                       │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ Application Info ═══════════════════════════════ ─ □ ✕ ┐  ┌══ PHP Info ══════════════ ─ □ ✕ ┐           │
│  │ Framework:   Yii 3.0.2                                  │  │ Version:   8.4.5                 │           │
│  │ Environment: development                                │  │ SAPI:      cli-server            │           │
│  │ Debug Mode:  enabled                                    │  │ OS:        Linux 6.18.5          │           │
│  │ Timezone:    UTC                                        │  │ Memory:    128M limit            │           │
│  │ Charset:     UTF-8                                      │  │ OPcache:   enabled               │           │
│  │ Root Path:   /var/www/app                               │  │ Xdebug:    3.4.0                │           │
│  │ Temp Path:   /var/www/app/runtime                       │  │ Extensions: 42 loaded           │           │
│  └─────────────────────────────────────────────────────────┘  └──────────────────────────────────┘           │
│                                                                                                                │
│  ┌══ Routes (48) ═══════════════════════════════════════════════════════════════════════ ─ □ ✕ ──┐            │
│  │                                                                                               │            │
│  │  🔍 Filter routes...                                                          [GET ▾] [All ▾] │            │
│  │                                                                                               │            │
│  │  Method   Pattern                     Handler                          Middleware              │            │
│  │  ──────   ──────────────────────────  ──────────────────────────────   ────────────────────    │            │
│  │  GET      /                           HomeController::index            auth, csrf              │            │
│  │  GET      /api/users                  UserController::list             auth, api               │            │
│  │  POST     /api/users                  UserController::create           auth, api, validate     │            │
│  │  GET      /api/users/{id}             UserController::view             auth, api               │            │
│  │  PUT      /api/users/{id}             UserController::update           auth, api, validate     │            │
│  │  DELETE   /api/users/{id}             UserController::delete           auth, api, admin        │            │
│  │  GET      /api/products               ProductController::index         auth, api               │            │
│  │  POST     /api/products               ProductController::create        auth, api, validate     │            │
│  │  GET      /api/products/{id}          ProductController::view          auth, api               │            │
│  │  GET      /dashboard                  DashboardController::index       auth, csrf              │            │
│  │  GET      /login                      AuthController::loginForm        guest                   │            │
│  │  POST     /login                      AuthController::login            guest, csrf             │            │
│  │                                                                                               │            │
│  │  Showing 12 of 48 routes                                                     Page 1 of 4 ▸    │            │
│  └───────────────────────────────────────────────────────────────────────────────────────────────┘            │
│                                                                                                                │
│  ┌══ Container (156) ════════════════════════════════ ─ □ ✕ ┐  ┌══ Commands (14) ════════ ─ □ ✕ ┐           │
│  │                                                          │  │                                  │           │
│  │  🔍 Filter services...                                    │  │  Command           Description   │           │
│  │                                                          │  │  ──────────────    ────────────  │           │
│  │  Service                         Type                    │  │  migrate           Run DB migr.  │           │
│  │  ─────────────────────────────   ───────                 │  │  migrate:create    Create migr.  │           │
│  │  LoggerInterface                 Singleton               │  │  migrate:down      Rollback      │           │
│  │  EventDispatcherInterface        Singleton               │  │  cache:clear       Clear cache   │           │
│  │  RouterInterface                 Singleton               │  │  user:create       Create user   │           │
│  │  DatabaseInterface               Factory                 │  │  user:list         List users    │           │
│  │  CacheInterface                  Singleton               │  │  debug:reset       Reset debug   │           │
│  │  SessionInterface                Factory                 │  │  serve             Start server  │           │
│  │  AuthenticatorInterface          Singleton               │  │  queue:work        Run worker    │           │
│  │  MailerInterface                 Singleton               │  │  queue:failed      Show failed   │           │
│  │  ValidatorInterface              Singleton               │  │  config:dump       Dump config   │           │
│  │  HttpClientInterface             Factory                 │  │  route:list        List routes   │           │
│  │                                                          │  │  asset:publish     Publish       │           │
│  │  Showing 10 of 156              Page 1 of 16 ▸           │  │  test              Run tests     │           │
│  └──────────────────────────────────────────────────────────┘  └──────────────────────────────────┘           │
│                                                                                                                │
│  ┌══ Event Listeners ════════════════════════════════════════════════════════════════════ ─ □ ✕ ──┐           │
│  │                                                                                               │           │
│  │  Event                                Listener                              Priority          │           │
│  │  ──────────────────────────────────   ──────────────────────────────────    ──────────        │           │
│  │  Application\Event\BeforeRequest      SecurityMiddleware::onRequest         100               │           │
│  │  Application\Event\BeforeRequest      LoggingMiddleware::onRequest          50                │           │
│  │  Application\Event\BeforeRequest      CorsMiddleware::onRequest             90                │           │
│  │  Application\Event\AfterRequest       LoggingMiddleware::onResponse         50                │           │
│  │  Application\Event\AfterRequest       SessionMiddleware::onResponse         40                │           │
│  │  Router\Event\RouteMatched            AuthorizationListener::onRoute        100               │           │
│  │  Database\Event\QueryExecuted         QueryLogListener::onQuery             50                │           │
│  │  User\Event\UserCreated               WelcomeEmailListener::onCreated       50                │           │
│  │  User\Event\UserCreated               AuditLogListener::onCreated           40                │           │
│  │                                                                                               │           │
│  └───────────────────────────────────────────────────────────────────────────────────────────────┘           │
│                                                                                                                │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Grid Positions (12-Column Grid)

```
  Col:  1    2    3    4    5    6    7    8    9    10   11   12
  Row 1 ┌─── App Info (7 cols) ──────┬─── PHP Info (5 cols) ──────┐
        │                            │                             │
  Row 2 └────────────────────────────┴─────────────────────────────┘
  Row 3 ┌─── Routes (12 cols, full width) ─────────────────────────┐
        │                                                          │
        │                                                          │
  Row 5 └─────────────────────────────────────────────────────────┘
  Row 6 ┌─── Container (7 cols) ─────┬─── Commands (5 cols) ──────┐
        │                            │                             │
        │                            │                             │
  Row 9 └────────────────────────────┴─────────────────────────────┘
  Row 10┌─── Event Listeners (12 cols, full width) ────────────────┐
        │                                                          │
  Row 11└──────────────────────────────────────────────────────────┘
```

## Container Widget — Expanded Service Detail

When clicking a service row in the Container widget, a detail panel expands below:

```
┌══ Container (156) ══════════════════════════════════════════════════════════════════════ ─ □ ✕ ──┐
│                                                                                                  │
│  🔍 Filter services...                                                                           │
│                                                                                                  │
│  Service                              Type          Tags                                         │
│  ──────────────────────────────────   ──────────    ──────────────────────────                   │
│  LoggerInterface                      Singleton     psr-3, logging                               │
│  ▼ EventDispatcherInterface           Singleton     psr-14, events                               │
│  ├─────────────────────────────────────────────────────────────────────────────────────────────┐ │
│  │  Class:      Yiisoft\EventDispatcher\EventDispatcher                                       │ │
│  │  File:       vendor/yiisoft/event-dispatcher/src/EventDispatcher.php                       │ │
│  │  Implements: Psr\EventDispatcher\EventDispatcherInterface                                  │ │
│  │  Dependencies:                                                                             │ │
│  │    - ListenerProviderInterface (Singleton)                                                 │ │
│  │    - LoggerInterface (Singleton)                                                           │ │
│  │  Created:    1 time during request                                                         │ │
│  └─────────────────────────────────────────────────────────────────────────────────────────────┘ │
│  RouterInterface                      Singleton     routing                                      │
│  DatabaseInterface                    Factory       database                                     │
│  CacheInterface                       Singleton     psr-16, cache                                │
│                                                                                                  │
│  Showing 5 of 156                                                              Page 1 of 32 ▸    │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Middleware Stack Widget (Optional Inspector Widget)

```
┌══ Middleware Stack ════════════════════════════════════════════════ ─ □ ✕ ┐
│                                                                          │
│  Order   Middleware                            Group                     │
│  ──────  ──────────────────────────────────    ──────────                │
│  1       ErrorHandlerMiddleware                framework                 │
│  2       SessionMiddleware                     framework                 │
│  3       CorsMiddleware                        security                  │
│  4       CsrfMiddleware                        security                  │
│  5       AuthenticationMiddleware              auth                      │
│  6       AuthorizationMiddleware               auth                      │
│  7       RouterMiddleware                      routing                   │
│  8       ActionMiddleware                      routing                   │
│                                                                          │
│  8 middleware in pipeline                                                │
└──────────────────────────────────────────────────────────────────────────┘
```

## Configuration Widget (Optional Inspector Widget)

```
┌══ Configuration ══════════════════════════════════════════════════════════════════════ ─ □ ✕ ────┐
│                                                                                                  │
│  🔍 Filter keys...                                                                               │
│                                                                                                  │
│  ▼ app                                                                                           │
│  │  name:          "My Application"                                                              │
│  │  version:       "1.2.3"                                                                       │
│  │  environment:   "development"                                                                 │
│  │  debug:         true                                                                          │
│  │  charset:       "UTF-8"                                                                       │
│  │  timezone:      "UTC"                                                                         │
│  ▼ database                                                                                      │
│  │  driver:        "pgsql"                                                                       │
│  │  host:          "localhost"                                                                    │
│  │  port:          5432                                                                          │
│  │  name:          "app_dev"                                                                     │
│  │  username:      "app_user"                                                                    │
│  │  password:      "********"                                                                    │
│  │  ▼ options                                                                                    │
│  │  │  persistent:  false                                                                        │
│  │  │  timeout:     5                                                                            │
│  │  │  charset:     "utf8"                                                                       │
│  ▶ cache                                                                                         │
│  ▶ mailer                                                                                        │
│  ▶ session                                                                                       │
│                                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Parameters Widget (Optional Inspector Widget)

```
┌══ Parameters ═══════════════════════════════════════ ─ □ ✕ ┐
│                                                            │
│  Parameter                   Value           Type          │
│  ──────────────────────────  ──────────────  ──────        │
│  app.name                    My Application  string        │
│  app.version                 1.2.3           string        │
│  app.debug                   true            boolean       │
│  db.dsn                      pgsql:host=...  string        │
│  db.pool_size                10              integer       │
│  cache.ttl                   3600            integer       │
│  mail.from                   no-reply@...    string        │
│  session.lifetime            7200            integer       │
│  auth.token_ttl              86400           integer       │
│                                                            │
│  9 parameters                                              │
└────────────────────────────────────────────────────────────┘
```

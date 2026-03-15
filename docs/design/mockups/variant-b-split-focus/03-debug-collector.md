# 03 — Collector Content (Accordion Sections)

## Request/Response Collector

### Expanded View

```
┌─ 📋 Request/Response ───────────────────────────────────────── 201 Created ── [📌▾]─┐
│                                                                                      │
│  ┌─ Request ───────────────────────────────────────────────────────────────────────┐  │
│  │  Method:   POST                          URL:  /api/users                      │  │
│  │  Host:     localhost:8080                Time: 45ms                             │  │
│  │  Size:     1.2 KB                        IP:   127.0.0.1                       │  │
│  ├─────────────────────────────────────────────────────────────────────────────────┤  │
│  │  Headers                                                                       │  │
│  │  ┌──────────────────────────┬──────────────────────────────────────────────┐    │  │
│  │  │ Content-Type             │ application/json                             │    │  │
│  │  │ Authorization            │ Bearer eyJhbGciOi...                        │    │  │
│  │  │ Accept                   │ application/json                             │    │  │
│  │  │ X-Request-ID             │ a1b2c3d4-e5f6-7890                          │    │  │
│  │  └──────────────────────────┴──────────────────────────────────────────────┘    │  │
│  ├─────────────────────────────────────────────────────────────────────────────────┤  │
│  │  Body                                                          [Raw] [Pretty]  │  │
│  │  ┌─────────────────────────────────────────────────────────────────────────┐    │  │
│  │  │  {                                                                     │    │  │
│  │  │    "name": "John Doe",                                                 │    │  │
│  │  │    "email": "john@example.com",                                        │    │  │
│  │  │    "role": "editor"                                                    │    │  │
│  │  │  }                                                                     │    │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
│  ┌─ Response ──────────────────────────────────────────────────────────────────────┐  │
│  │  Status:  201 Created                    Time: 45ms                            │  │
│  │  Size:    256 B                                                                │  │
│  ├─────────────────────────────────────────────────────────────────────────────────┤  │
│  │  Headers                                                                       │  │
│  │  ┌──────────────────────────┬──────────────────────────────────────────────┐    │  │
│  │  │ Content-Type             │ application/json                             │    │  │
│  │  │ X-Debug-ID               │ a1b2c3                                       │    │  │
│  │  │ Location                 │ /api/users/42                                │    │  │
│  │  └──────────────────────────┴──────────────────────────────────────────────┘    │  │
│  ├─────────────────────────────────────────────────────────────────────────────────┤  │
│  │  Body                                                          [Raw] [Pretty]  │  │
│  │  ┌─────────────────────────────────────────────────────────────────────────┐    │  │
│  │  │  {                                                                     │    │  │
│  │  │    "id": 42,                                                           │    │  │
│  │  │    "name": "John Doe",                                                 │    │  │
│  │  │    "email": "john@example.com",                                        │    │  │
│  │  │    "created_at": "2026-03-15T14:23:07Z"                                │    │  │
│  │  │  }                                                                     │    │  │
│  │  └─────────────────────────────────────────────────────────────────────────┘    │  │
│  └─────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

## Database Collector

### Expanded View

```
┌─ 🗄  Database ─────────────────────────────────────── 3 queries, 12ms total ── [📌▾]─┐
│                                                                                       │
│  Summary:  3 queries  |  12ms total  |  7 rows affected  |  0 errors  |  0 slow      │
│                                                                                       │
│  ┌────┬──────────────────────────────────────────────────────┬───────┬──────┬────────┐ │
│  │ #  │ Query                                                │ Time  │ Rows │ Status │ │
│  ├────┼──────────────────────────────────────────────────────┼───────┼──────┼────────┤ │
│  │ 1  │ INSERT INTO users (name, email, created_at) VALUE... │  8ms  │   1  │ OK     │ │
│  │ 2  │ SELECT * FROM roles WHERE active = 1                 │  2ms  │   3  │ OK     │ │
│  │ 3  │ INSERT INTO user_roles (user_id, role_id) VALUES ... │  2ms  │   3  │ OK     │ │
│  └────┴──────────────────────────────────────────────────────┴───────┴──────┴────────┘ │
│                                                                                       │
│  Click a query row to see full SQL, parameters, and execution plan.                   │
│                                                                                       │
└───────────────────────────────────────────────────────────────────────────────────────┘
```

### Query Detail (click to expand row)

```
│  ┌────┬──────────────────────────────────────────────────────┬───────┬──────┬────────┐ │
│  │ 1  │ INSERT INTO users (name, email, created_at) VALUE... │  8ms  │   1  │ OK     │ │
│  ├────┴──────────────────────────────────────────────────────┴───────┴──────┴────────┤ │
│  │  Full Query:                                                          [Copy SQL] │ │
│  │  ┌───────────────────────────────────────────────────────────────────────────┐    │ │
│  │  │  INSERT INTO users (name, email, created_at)                             │    │ │
│  │  │  VALUES (:name, :email, :created_at)                                     │    │ │
│  │  └───────────────────────────────────────────────────────────────────────────┘    │ │
│  │                                                                                  │ │
│  │  Parameters:                                                                     │ │
│  │  ┌──────────────────┬──────────────────────────────┬──────────┐                  │ │
│  │  │ Name             │ Value                        │ Type     │                  │ │
│  │  ├──────────────────┼──────────────────────────────┼──────────┤                  │ │
│  │  │ :name            │ "John Doe"                   │ string   │                  │ │
│  │  │ :email           │ "john@example.com"           │ string   │                  │ │
│  │  │ :created_at      │ "2026-03-15T14:23:07Z"       │ string   │                  │ │
│  │  └──────────────────┴──────────────────────────────┴──────────┘                  │ │
│  │                                                                                  │ │
│  │  Call Stack:                                                                     │ │
│  │  App\Service\UserService::create()          src/Service/UserService.php:42       │ │
│  │  App\Controller\UserController::store()     src/Controller/UserController.php:28 │ │
│  ├────┬──────────────────────────────────────────────────────┬───────┬──────┬────────┤ │
│  │ 2  │ SELECT * FROM roles WHERE active = 1                 │  2ms  │   3  │ OK     │ │
```

## Log Collector

### Expanded View

```
┌─ 📝 Log ────────────────────────────────────── 5 entries, 1 error, 1 warning ── [📌▾]─┐
│                                                                                        │
│  [All] [Debug: 2] [Info: 1] [Warning: 1] [Error: 1]          [🔍 Filter logs...]     │
│                                                                                        │
│  ┌────────────┬─────────┬────────────────────────────────────────┬──────────────────┐  │
│  │ Time       │ Level   │ Message                                │ Category         │  │
│  ├────────────┼─────────┼────────────────────────────────────────┼──────────────────┤  │
│  │ 14:23:07.1 │ DEBUG   │ Routing matched: POST /api/users       │ router           │  │
│  │ 14:23:07.1 │ DEBUG   │ Resolving UserController::store        │ di               │  │
│  │ 14:23:07.2 │ INFO    │ Creating new user: john@example.com    │ app.user         │  │
│  │ 14:23:07.3 │ WARNING │ Email domain not in allowlist           │ app.validation   │  │
│  │ 14:23:07.4 │ ERROR   │ Failed to send welcome email: SMTP ... │ app.mailer       │  │
│  └────────────┴─────────┴────────────────────────────────────────┴──────────────────┘  │
│                                                                                        │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

### Log Level Color Coding

```
│  14:23:07.1 │ DEBUG   │ ...  │   <-- gray text
│  14:23:07.2 │ INFO    │ ...  │   <-- default text
│  14:23:07.3 │ WARNING │ ...  │   <-- orange text, orange left border
│  14:23:07.4 │ ERROR   │ ...  │   <-- red text, red left border, red tint background
```

### Log Detail (click to expand)

```
│  │ 14:23:07.4 │ ERROR   │ Failed to send welcome email: SMTP ... │ app.mailer       │  │
│  ├────────────┴─────────┴────────────────────────────────────────┴──────────────────┤  │
│  │  Full Message:                                                                   │  │
│  │  Failed to send welcome email: SMTP connection refused (111)                     │  │
│  │                                                                                  │  │
│  │  Context:                                                                        │  │
│  │  ┌───────────────────────────────────────────────────────────────────────────┐    │  │
│  │  │  {                                                                       │    │  │
│  │  │    "to": "john@example.com",                                             │    │  │
│  │  │    "template": "welcome",                                                │    │  │
│  │  │    "smtp_host": "mail.example.com",                                      │    │  │
│  │  │    "error_code": 111                                                     │    │  │
│  │  │  }                                                                       │    │  │
│  │  └───────────────────────────────────────────────────────────────────────────┘    │  │
│  │                                                                                  │  │
│  │  Stack Trace:                                                                    │  │
│  │  App\Service\MailerService::send()         src/Service/MailerService.php:78      │  │
│  │  App\Listener\UserCreatedListener::__i...  src/Listener/UserCreated.php:23      │  │
│  ├────────────┬─────────┬────────────────────────────────────────┬──────────────────┤  │
```

## Event Collector

### Expanded View

```
┌─ 📡 Events ────────────────────────────────────────────── 12 events fired ── [📌▾]──┐
│                                                                                      │
│  [🔍 Filter events...]                                                               │
│                                                                                      │
│  ┌────┬────────────┬───────────────────────────────────┬──────────┬────────┬────────┐ │
│  │ #  │ Time       │ Event                             │ Listeners│ Time   │ Status │ │
│  ├────┼────────────┼───────────────────────────────────┼──────────┼────────┼────────┤ │
│  │ 1  │ 14:23:07.0 │ Router\BeforeRoute                │    2     │  1ms   │ OK     │ │
│  │ 2  │ 14:23:07.0 │ Router\AfterRoute                 │    1     │  0ms   │ OK     │ │
│  │ 3  │ 14:23:07.1 │ Controller\BeforeAction            │    3     │  2ms   │ OK     │ │
│  │ 4  │ 14:23:07.1 │ App\Event\UserCreating            │    2     │  1ms   │ OK     │ │
│  │ 5  │ 14:23:07.3 │ App\Event\UserCreated             │    3     │ 15ms   │ WARN   │ │
│  │ 6  │ 14:23:07.3 │ Mailer\BeforeSend                 │    1     │  0ms   │ OK     │ │
│  │ 7  │ 14:23:07.4 │ Mailer\SendFailed                 │    1     │  0ms   │ ERROR  │ │
│  │ 8  │ 14:23:07.4 │ Controller\AfterAction             │    2     │  1ms   │ OK     │ │
│  │ .. │ ...        │ (4 more events)                   │          │        │        │ │
│  └────┴────────────┴───────────────────────────────────┴──────────┴────────┴────────┘ │
│                                                                                      │
│  Showing 8 of 12 events                                               [Show all]     │
│                                                                                      │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

## Service/Container Collector

### Expanded View

```
┌─ 🧩 Service Container ──────────────────────────── 8 services resolved ── [📌▾]─────┐
│                                                                                      │
│  [🔍 Filter services...]                                                             │
│                                                                                      │
│  ┌──────────────────────────────────────────┬───────────┬────────┬──────────────────┐ │
│  │ Service                                  │ Time      │ Cached │ Type             │ │
│  ├──────────────────────────────────────────┼───────────┼────────┼──────────────────┤ │
│  │ App\Controller\UserController            │  3ms      │ No     │ Transient        │ │
│  │ App\Service\UserService                  │  1ms      │ No     │ Transient        │ │
│  │ App\Repository\UserRepository            │  2ms      │ Yes    │ Singleton        │ │
│  │ Psr\Log\LoggerInterface                  │  0ms      │ Yes    │ Singleton        │ │
│  │ Cycle\ORM\EntityManagerInterface         │  0ms      │ Yes    │ Singleton        │ │
│  │ App\Service\MailerService                │  1ms      │ No     │ Transient        │ │
│  │ Psr\EventDispatcher\EventDispatcherI...  │  0ms      │ Yes    │ Singleton        │ │
│  │ App\Validator\UserValidator              │  1ms      │ No     │ Transient        │ │
│  └──────────────────────────────────────────┴───────────┴────────┴──────────────────┘ │
│                                                                                      │
│  Total resolution time: 8ms  |  4 cached  |  4 transient                             │
│                                                                                      │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

## Exception Collector

### Expanded View (when exception occurred)

```
┌─ 💥 Exception ──────────────────────────────────── RuntimeException ── [📌▾]─────────┐
│                                                                                      │
│  ┌─ RuntimeException ─────────────────────────────────────────────────────────────┐  │
│  │                                                                                │  │
│  │  SMTP connection refused (111)                                                 │  │
│  │                                                                                │  │
│  │  Class:  RuntimeException                                                      │  │
│  │  Code:   111                                                                   │  │
│  │  File:   src/Service/MailerService.php:78                                      │  │
│  │                                                                                │  │
│  │  Stack Trace:                                                                  │  │
│  │  ──────────────────────────────────────────────────────────────────────────     │  │
│  │  #0  App\Service\MailerService::send()                                         │  │
│  │      src/Service/MailerService.php:78                                          │  │
│  │  #1  App\Listener\UserCreatedListener::__invoke()                              │  │
│  │      src/Listener/UserCreatedListener.php:23                                   │  │
│  │  #2  Yiisoft\EventDispatcher\Dispatcher::dispatch()                            │  │
│  │      vendor/yiisoft/event-dispatcher/src/Dispatcher.php:45                     │  │
│  │  #3  App\Service\UserService::create()                                         │  │
│  │      src/Service/UserService.php:56                                            │  │
│  │  #4  App\Controller\UserController::store()                                    │  │
│  │      src/Controller/UserController.php:28                                      │  │
│  │                                                                                │  │
│  └────────────────────────────────────────────────────────────────────────────────┘  │
│                                                                                      │
└──────────────────────────────────────────────────────────────────────────────────────┘
```

## Accordion Reordering (Drag and Drop)

```
Before drag:                          During drag:
┌─ 📋 Request/Response ─── [▴]──┐    ┌─ 📋 Request/Response ─── [▴]──┐
│  ...                          │    │  ...                          │
└───────────────────────────────┘    └───────────────────────────────┘
┌─ 🗄  Database ────────── [▴]──┐    ┌ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─┐
│  ...                          │      Drop 📝 Log here
└───────────────────────────────┘    └ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─ ─┘
┌─ 📝 Log ─────────────── [▴]──┐    ┌─ 📝 Log ─────────────── [▴]──┐  <-- being dragged
│  ...                          │    │  ...                    ░░░░  │     (shadow + opacity)
└───────────────────────────────┘    └───────────────────────────────┘
┌─ 📡 Events ──────────── [▾]──┐    ┌─ 🗄  Database ────────── [▴]──┐
│  (collapsed)                  │    │  ...                          │
└───────────────────────────────┘    └───────────────────────────────┘
                                     ┌─ 📡 Events ──────────── [▾]──┐
                                     │  (collapsed)                  │
                                     └───────────────────────────────┘
```

# Variant E: Dashboard Grid — Widget Fullscreen View

## Overview

Any widget can be maximized to fill the entire grid canvas. This is useful for detailed inspection
of data-heavy widgets like query tables, log streams, or JSON trees. The shell header remains visible.
Press Escape or click the restore button to return to the grid layout.

## Fullscreen: DB Queries Table

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/users ▾  #a3f7c1  2026-03-15 14:32:07          200 OK 145ms         + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐ ┌───────────┐                                                                     │
│  │ Debug   │ │ Inspector │ │ Perf      │  +                                                                   │
│  │ ▀▀▀▀▀▀▀ │ │           │ │           │                                                                     │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ DB Queries (8) ═══════════════════════════════════════════════════════════════════════ ─ ◻ ✕ ──┐         │
│  │                                                                                                  │         │
│  │  🔍 Filter queries...                [Slow > 10ms ☐]  [Failed ☐]  [With params ☐]  [Export ▾]   │         │
│  │                                                                                                  │         │
│  │  #    Query                                              Duration ▾  Rows   Params   Connection  │         │
│  │  ──── ────────────────────────────────────────────────   ─────────  ─────  ───────  ──────────   │         │
│  │  1    SELECT * FROM "user"                               12.3ms     42     1        default      │         │
│  │       WHERE "active" = :active                                                                   │         │
│  │       ├─ Params: {:active => 1}                                                                  │         │
│  │       ├─ Source: UserRepository::findActive() at line 42                                         │         │
│  │       └─ Explain: Seq Scan on user (rows=42, width=124)                                         │         │
│  │                                                                                                  │         │
│  │  2    SELECT * FROM "role"                               8.7ms      5      3        default      │         │
│  │       WHERE "id" IN (:id0, :id1, :id2)                                                           │         │
│  │       ├─ Params: {:id0 => 1, :id1 => 2, :id2 => 3}                                              │         │
│  │       ├─ Source: RoleRepository::findByIds() at line 28                                          │         │
│  │       └─ Explain: Index Scan using role_pkey (rows=5)                                            │         │
│  │                                                                                                  │         │
│  │  3    INSERT INTO "audit_log" (user_id, action,          5.7ms      1      3        default      │         │
│  │       created_at) VALUES (:uid, :action, :created)                                               │         │
│  │       ├─ Params: {:uid => 1, :action => 'list', :created => '2026-03-15 14:32:07'}              │         │
│  │       └─ Source: AuditService::log() at line 15                                                  │         │
│  │                                                                                                  │         │
│  │  4    SELECT * FROM "permission"                         5.2ms      12     2        default      │         │
│  │       WHERE "role_id" IN (:r0, :r1)                                                              │         │
│  │       ├─ Params: {:r0 => 1, :r1 => 2}                                                           │         │
│  │       └─ Source: PermissionRepository::findByRoles() at line 35                                  │         │
│  │                                                                                                  │         │
│  │  5    SELECT * FROM "user_profile"                       4.5ms      42     42       default      │         │
│  │       WHERE "user_id" IN (:u0, :u1, :u2, ...)                                                    │         │
│  │       ├─ Params: {:u0 => 1, :u1 => 2, ..., :u41 => 42}                                          │         │
│  │       └─ Source: UserProfileRepository::findByUsers() at line 22                                 │         │
│  │                                                                                                  │         │
│  │  6    SELECT COUNT(*) FROM "user"                        3.1ms      1      1        default      │         │
│  │       WHERE "active" = :active                                                                   │         │
│  │       ├─ Params: {:active => 1}                                                                  │         │
│  │       └─ Source: UserRepository::countActive() at line 50                                        │         │
│  │                                                                                                  │         │
│  │  7    SELECT * FROM "session"                            2.1ms      1      1        default      │         │
│  │       WHERE "user_id" = :uid LIMIT 1                                                             │         │
│  │       ├─ Params: {:uid => 1}                                                                     │         │
│  │       └─ Source: SessionRepository::findByUser() at line 18                                      │         │
│  │                                                                                                  │         │
│  │  8    SELECT * FROM "setting"                            1.2ms      1      1        default      │         │
│  │       WHERE "key" = :key                                                                         │         │
│  │       ├─ Params: {:key => 'pagination_size'}                                                     │         │
│  │       └─ Source: SettingRepository::get() at line 12                                             │         │
│  │                                                                                                  │         │
│  ├──────────────────────────────────────────────────────────────────────────────────────────────────┤         │
│  │  Total: 8 queries  │  42.8ms  │  105 rows  │  0 failed  │  1 connection        Page 1 of 1      │         │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────┘         │
│                                                                                                                │
│                                                                             Press Escape to return to grid     │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Note: the maximize button changes from `□` to `◻` (filled) when in fullscreen mode to indicate "restore".

## Fullscreen: Log Stream

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  GET /api/users ▾  #a3f7c1  2026-03-15 14:32:07          200 OK 145ms         + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐ ┌───────────┐                                                                     │
│  │ Debug   │ │ Inspector │ │ Perf      │  +                                                                   │
│  │ ▀▀▀▀▀▀▀ │ │           │ │           │                                                                     │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ Logs (23) ═══════════════════════════════════════════════════════════════════════════ ─ ◻ ✕ ──┐          │
│  │                                                                                                 │          │
│  │  🔍 Search logs...           [ALL ▾]  [INFO ☑] [DEBUG ☑] [WARN ☑] [ERROR ☑]  [Wrap ☐] [Raw ☐] │          │
│  │                                                                                                 │          │
│  │  Timestamp        Level    Channel       Message                                                │          │
│  │  ───────────────  ───────  ────────────  ──────────────────────────────────────────────────────  │          │
│  │  14:32:07.012     INFO     application   App booted successfully                                │          │
│  │  14:32:07.013     DEBUG    container     Resolving RouterInterface (cached)                      │          │
│  │  14:32:07.014     DEBUG    container     Resolving LoggerInterface (cached)                      │          │
│  │  14:32:07.015     DEBUG    router        Matching route for GET /api/users                       │          │
│  │  14:32:07.034     DEBUG    router        Route matched: api/users -> UserController::list        │          │
│  │  14:32:07.035     INFO     controller    Executing action: UserController::list                  │          │
│  │  14:32:07.036     DEBUG    container     Creating DatabaseInterface (factory)                    │          │
│  │  14:32:07.041     DEBUG    database      Opening connection to pgsql:host=localhost;dbname=...   │          │
│  │  14:32:07.053     DEBUG    database      Query: SELECT * FROM "user" WHERE "active" = 1         │          │
│  │                                          Duration: 12.3ms, Rows: 42                             │          │
│  │  14:32:07.066     DEBUG    database      Query: SELECT COUNT(*) FROM "user" WHERE "active" = 1  │          │
│  │                                          Duration: 3.1ms, Rows: 1                               │          │
│  │  14:32:07.074     DEBUG    database      Query: SELECT * FROM "role" WHERE "id" IN (1, 2, 3)    │          │
│  │                                          Duration: 8.7ms, Rows: 5                               │          │
│  │  14:32:07.080     DEBUG    database      Query: SELECT * FROM "permission" WHERE...              │          │
│  │                                          Duration: 5.2ms, Rows: 12                              │          │
│  │  14:32:07.085     DEBUG    database      Query: SELECT * FROM "session" WHERE "user_id" = 1     │          │
│  │                                          Duration: 2.1ms, Rows: 1                               │          │
│  │  14:32:07.089     INFO     cache         Cache hit: user_permissions_1 (key size: 24 bytes)     │          │
│  │  14:32:07.092     DEBUG    database      Query: SELECT * FROM "user_profile" WHERE...           │          │
│  │                                          Duration: 4.5ms, Rows: 42                              │          │
│  │  14:32:07.097     DEBUG    database      Query: SELECT * FROM "setting" WHERE...                │          │
│  │                                          Duration: 1.2ms, Rows: 1                               │          │
│  │  14:32:07.100     DEBUG    database      Query: INSERT INTO "audit_log"...                      │          │
│  │                                          Duration: 5.7ms, Rows: 1                               │          │
│  │  14:32:07.110     INFO     serializer    Serializing 42 User entities to JSON                   │          │
│  │  14:32:07.130     WARN     deprecation   Method User::getFullName() is deprecated since v1.1    │          │
│  │  14:32:07.131     WARN     deprecation   Method User::getRole() is deprecated since v1.2        │          │
│  │  14:32:07.132     WARN     memory        Peak memory usage: 12.4 MB (threshold: 16 MB)         │          │
│  │  14:32:07.142     DEBUG    response      Sending 200 response, body size: 2.4 KB                │          │
│  │  14:32:07.144     INFO     application   Request completed in 145ms                             │          │
│  │                                                                                                 │          │
│  ├─────────────────────────────────────────────────────────────────────────────────────────────────┤          │
│  │  INFO: 5  │  DEBUG: 14  │  WARN: 3  │  ERROR: 1  │  Total: 23                                  │          │
│  └─────────────────────────────────────────────────────────────────────────────────────────────────┘          │
│                                                                                                                │
│                                                                             Press Escape to return to grid     │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Fullscreen: JSON Tree

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ADP   ◀ ▶  POST /api/users ▾  #c4d9f2  2026-03-15 14:31:55        201 Created 230ms     + ✎ ⚙              │
│  ┌─────────┐ ┌───────────┐                                                                                   │
│  │ Debug   │ │ Inspector │  +                                                                                 │
│  │ ▀▀▀▀▀▀▀ │ │           │                                                                                   │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                                │
│  ┌══ Request Body ═══════════════════════════════════════════════════════════════════════ ─ ◻ ✕ ──┐           │
│  │                                                                                               │           │
│  │  🔍 Search keys/values...                       [Expand all]  [Collapse]  [Copy]  [Raw JSON]  │           │
│  │                                                                                               │           │
│  │  ▼ {                                                             object (6 keys)              │           │
│  │  │  "name": "John Doe"                                          string (8 chars)              │           │
│  │  │  "email": "john.doe@example.com"                              string (22 chars)             │           │
│  │  │  "password": "********"                                       string (redacted)             │           │
│  │  │  ▼ "roles": [                                                 array (2 items)              │           │
│  │  │  │  0: "user"                                                 string                       │           │
│  │  │  │  1: "editor"                                               string                       │           │
│  │  │  ]                                                                                         │           │
│  │  │  ▼ "profile": {                                               object (4 keys)              │           │
│  │  │  │  "first_name": "John"                                      string                       │           │
│  │  │  │  "last_name": "Doe"                                        string                       │           │
│  │  │  │  "timezone": "America/New_York"                             string                       │           │
│  │  │  │  ▼ "preferences": {                                        object (3 keys)              │           │
│  │  │  │  │  "theme": "dark"                                        string                       │           │
│  │  │  │  │  "notifications": true                                  boolean                      │           │
│  │  │  │  │  "language": "en"                                       string                       │           │
│  │  │  │  }                                                                                      │           │
│  │  │  }                                                                                         │           │
│  │  │  ▼ "metadata": {                                              object (2 keys)              │           │
│  │  │  │  "source": "registration_form"                             string                       │           │
│  │  │  │  "ip": "192.168.1.100"                                     string                       │           │
│  │  │  }                                                                                         │           │
│  │  }                                                                                            │           │
│  │                                                                                               │           │
│  ├───────────────────────────────────────────────────────────────────────────────────────────────┤           │
│  │  6 root keys  │  18 total nodes  │  3 depth levels  │  Raw: 487 bytes                         │           │
│  └───────────────────────────────────────────────────────────────────────────────────────────────┘           │
│                                                                                                                │
│                                                                             Press Escape to return to grid     │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Transition Between States

```
  GRID VIEW                        FULLSCREEN VIEW                    GRID VIEW
  ──────────                       ───────────────                    ──────────

  ┌────┬────┐                     ┌───────────────┐                  ┌────┬────┐
  │ A  │ B  │   Click □ on B      │               │  Press Escape    │ A  │ B  │
  ├────┼────┤   ──────────────▶   │    B (full)   │  ─────────────▶  ├────┼────┤
  │ C  │ D  │                     │               │                  │ C  │ D  │
  └────┴────┘                     └───────────────┘                  └────┴────┘

  All widgets visible             Only B visible,                    All widgets restored
                                  other widgets hidden               to previous positions
```

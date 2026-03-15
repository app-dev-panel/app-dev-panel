# Variant E: Dashboard Grid — Widget Types

## Overview

Six core widget types cover all data visualization needs. Each type has a consistent chrome
(title bar, controls, resize handles) and a type-specific content area.

## 1. Status Card Widget

Displays key-value pairs in a compact summary. Used for request metadata, PHP info, response overview.

```
┌══ Request Info ══════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐                   │
│  │  Method               │  │  Status               │  │  Duration             │                   │
│  │  GET                  │  │  200 OK                │  │  145ms                │                   │
│  └──────────────────────┘  └──────────────────────┘  └──────────────────────┘                   │
│  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐                   │
│  │  Memory               │  │  PHP Version          │  │  Route                │                   │
│  │  12.4 MB              │  │  8.4.5                │  │  api/users            │                   │
│  └──────────────────────┘  └──────────────────────┘  └──────────────────────┘                   │
│                                                                                                  │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Alternative: inline key-value layout for narrow widgets:

```
┌══ Request Info ═══════════════════════ ─ □ ✕ ┐
│                                              │
│  Method:    GET                              │
│  URL:       /api/users                       │
│  Route:     api/users                        │
│  Handler:   UserController::list             │
│  Time:      145ms                            │
│  Memory:    12.4 MB                          │
│  PHP:       8.4.5                            │
│                                              │
└──────────────────────────────────────────────┘
```

## 2. Table Widget

Sortable, filterable data grid with pagination. Used for queries, events, services, routes.

```
┌══ DB Queries (8) ════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  🔍 Filter queries...                                          [Slow only ☐]  [Failed only ☐]   │
│                                                                                                  │
│  #    Query                                         Duration ▾   Rows     Status                │
│  ──── ─────────────────────────────────────────     ──────────   ──────   ──────                 │
│  1    SELECT * FROM "user" WHERE "active" = 1      12.3ms       42       OK                     │
│  2    SELECT * FROM "role" WHERE "id" IN (1,2,3)   8.7ms        5        OK                     │
│  3    SELECT * FROM "permission" WHERE "role_id"    5.2ms        12       OK                     │
│       IN (1, 2)                                                                                  │
│  4    INSERT INTO "audit_log" (user_id, action,     5.7ms        1        OK                     │
│       created_at) VALUES (1, 'list', NOW())                                                      │
│  5    SELECT * FROM "user_profile" WHERE            4.5ms        42       OK                     │
│       "user_id" IN (1, 2, 3, ...)                                                                │
│  6    SELECT COUNT(*) FROM "user" WHERE             3.1ms        1        OK                     │
│       "active" = 1                                                                               │
│  7    SELECT * FROM "session" WHERE                 2.1ms        1        OK                     │
│       "user_id" = 1 LIMIT 1                                                                     │
│  8    SELECT * FROM "setting" WHERE                 1.2ms        1        OK                     │
│       "key" = 'pagination_size'                                                                  │
│                                                                                                  │
├──────────────────────────────────────────────────────────────────────────────────────────────────┤
│  Total: 8 queries  │  42.8ms total  │  105 rows  │  0 failed                     Page 1 of 1    │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Column sort indicator: `▾` descending, `▴` ascending, no indicator = unsorted.

## 3. Log Stream Widget

Scrolling log output with level filtering and search. Color-coded by severity level.

```
┌══ Logs (23) ══════════════════════════════════════════════════════════════════════════ ─ □ ✕ ────┐
│                                                                                                  │
│  🔍 Search logs...               [ALL ▾]  [INFO ☑] [DEBUG ☑] [WARN ☑] [ERROR ☑]                │
│                                                                                                  │
│  14:32:07.012  INFO    application   App booted successfully                                     │
│  14:32:07.015  DEBUG   router        Matching route for GET /api/users                           │
│  14:32:07.034  DEBUG   router        Route matched: api/users -> UserController::list            │
│  14:32:07.035  INFO    controller    Executing action: UserController::list                      │
│  14:32:07.041  DEBUG   database      Opening connection to pgsql:host=localhost;dbname=app_dev   │
│  14:32:07.053  DEBUG   database      Query: SELECT * FROM "user" WHERE "active" = 1 [12.3ms]    │
│  14:32:07.066  DEBUG   database      Query: SELECT COUNT(*) FROM "user" WHERE... [3.1ms]        │
│  14:32:07.074  DEBUG   database      Query: SELECT * FROM "role" WHERE... [8.7ms]               │
│  14:32:07.080  DEBUG   database      Query: SELECT * FROM "permission" WHERE... [5.2ms]         │
│  14:32:07.085  DEBUG   database      Query: SELECT * FROM "session" WHERE... [2.1ms]            │
│  14:32:07.089  INFO    cache         Cache hit: user_permissions_1                               │
│  14:32:07.092  DEBUG   database      Query: SELECT * FROM "user_profile" WHERE... [4.5ms]       │
│  14:32:07.097  DEBUG   database      Query: SELECT * FROM "setting" WHERE... [1.2ms]            │
│  14:32:07.100  DEBUG   database      Query: INSERT INTO "audit_log"... [5.7ms]                  │
│  14:32:07.110  INFO    serializer    Serializing 42 User entities                                │
│  14:32:07.130  WARN    deprecation   Method User::getFullName() is deprecated since v1.1        │
│  14:32:07.131  WARN    deprecation   Method User::getRole() is deprecated since v1.2            │
│  14:32:07.132  WARN    memory        Peak memory usage: 12.4 MB (threshold: 16 MB)              │
│  14:32:07.142  DEBUG   response      Sending 200 response (2.4 KB)                              │
│  14:32:07.144  INFO    application   Request completed in 145ms                                  │
│                                                                                                  │
├──────────────────────────────────────────────────────────────────────────────────────────────────┤
│  INFO: 5  │  DEBUG: 14  │  WARN: 3  │  ERROR: 1                         Showing 20 of 23 ▾     │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## 4. Chart Widget

Visualizes numeric data as bar charts, sparklines, or pie charts. Used for performance, memory, timing.

### Bar Chart: Query Duration

```
┌══ Query Duration ════════════════════════════════════════════════════════════════════ ─ □ ✕ ─────┐
│                                                                                                  │
│  12ms ┤                                                                                          │
│       │  ████                                                                                    │
│  10ms ┤  ████                                                                                    │
│       │  ████   ████                                                                             │
│   8ms ┤  ████   ████                                                                             │
│       │  ████   ████                                                                             │
│   6ms ┤  ████   ████   ████   ████                                                               │
│       │  ████   ████   ████   ████                                                               │
│   4ms ┤  ████   ████   ████   ████   ████                                                        │
│       │  ████   ████   ████   ████   ████   ████                                                 │
│   2ms ┤  ████   ████   ████   ████   ████   ████   ████   ████                                   │
│       │  ████   ████   ████   ████   ████   ████   ████   ████                                   │
│   0ms ┤──████───████───████───████───████───████───████───████──                                 │
│         Q1     Q2     Q3     Q4     Q5     Q6     Q7     Q8                                      │
│                                                                                                  │
│  Avg: 5.4ms  │  Max: 12.3ms  │  Total: 42.8ms                                                   │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

### Pie Chart: Log Level Distribution

```
┌══ Log Levels ═══════════════════════════ ─ □ ✕ ┐
│                                                 │
│          ██████████████                         │
│       ████            ████                      │
│     ███   DEBUG (61%)    ███                    │
│    ██                      ██                   │
│    ██                      ██  ── INFO  (22%)   │
│    ██                     ███                    │
│     ███                 ███  ── WARN  (13%)     │
│       ████           ████                       │
│          ██████████████  ── ERROR  (4%)         │
│                                                 │
│  Total: 23 log entries                          │
└─────────────────────────────────────────────────┘
```

### Sparkline: Memory Over Time

```
┌══ Memory Usage ═════════════════════════════════════════════ ─ □ ✕ ┐
│                                                                    │
│  16MB ┤                                                            │
│       │                                    ╭──╮                    │
│  12MB ┤                              ╭─────╯  ╰──╮   ╭──╮        │
│       │                         ╭────╯            ╰───╯  ╰──╮     │
│   8MB ┤                   ╭─────╯                            │     │
│       │             ╭─────╯                                  │     │
│   4MB ┤       ╭─────╯                                        │     │
│       │  ╭────╯                                              │     │
│   0MB ┤──╯                                                   ╰──   │
│        Boot    Route   Action    DB      Cache   Serialize  Done   │
│                                                                    │
│  Peak: 12.4 MB at Action phase                                     │
└────────────────────────────────────────────────────────────────────┘
```

## 5. JSON Tree Widget

Collapsible tree view for structured data. Used for request bodies, config dumps, container state.

```
┌══ Request Body ═════════════════════════════════════════════════════════════════════ ─ □ ✕ ──────┐
│                                                                                                  │
│  🔍 Search keys/values...                                              [Expand all] [Collapse]   │
│                                                                                                  │
│  ▼ {                                                  object (4 keys)                            │
│  │  ▼ "filters": {                                    object (3 keys)                            │
│  │  │    "status": "active"                           string                                     │
│  │  │    "role": "admin"                              string                                     │
│  │  │    ▼ "created_after": {                         object (2 keys)                            │
│  │  │    │    "date": "2026-01-01"                    string                                     │
│  │  │    │    "inclusive": true                        boolean                                    │
│  │  │    }                                                                                       │
│  │  }                                                                                            │
│  │  ▼ "pagination": {                                 object (2 keys)                            │
│  │  │    "page": 1                                    number                                     │
│  │  │    "per_page": 25                               number                                     │
│  │  }                                                                                            │
│  │  ▶ "sort": { ... }                                 object (2 keys)                            │
│  │  ▶ "fields": [ ... ]                               array (5 items)                            │
│  }                                                                                               │
│                                                                                                  │
│  4 root keys  │  14 total nodes  │  Raw JSON: 342 bytes                         [Copy] [Raw]    │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## 6. Timeline Widget

Horizontal bar timeline showing phases of request execution. Used for profiling and performance analysis.

```
┌══ Request Timeline ═════════════════════════════════════════════════════════════════ ─ □ ✕ ──────┐
│                                                                                                  │
│  0ms          25ms          50ms          75ms         100ms        125ms        145ms            │
│  ├─────────────┼─────────────┼─────────────┼────────────┼────────────┼───────────┤               │
│                                                                                                  │
│  Total    █████████████████████████████████████████████████████████████████████████  145ms        │
│  Boot     █████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  18ms         │
│  Route    ░░░░░░░░░████████████░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░  19ms         │
│  Action   ░░░░░░░░░░░░░░░░░░░░████████████████████████████████████░░░░░░░░░░░░░░  78ms         │
│  ├ DB     ░░░░░░░░░░░░░░░░░░░░░░░░░░░████████████████░░░░░░░░░░░░░░░░░░░░░░░░░░  43ms         │
│  ├ Cache  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░██░░░░░░░░░░░░░░░░░░░░░░░░   3ms         │
│  ├ Serialize ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░██████████████░░░░░░░░░░  20ms         │
│  View     ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░████████░  15ms         │
│  Response ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░██  3ms          │
│                                                                                                  │
│  Hover a bar for details. Click to filter related logs and queries.                              │
└──────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Widget States

### Loading State

```
┌══ DB Queries ═══════════════════════════════════════ ─ □ ✕ ┐
│                                                            │
│                                                            │
│               Loading query data...                        │
│               ████████░░░░░░░░░░░░                         │
│                                                            │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### Error State

```
┌══ DB Queries ═══════════════════════════════════════ ─ □ ✕ ┐
│                                                            │
│                                                            │
│            Failed to load query data                       │
│            Error: Connection refused                       │
│                                                            │
│                      [ Retry ]                             │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### Empty State

```
┌══ DB Queries ═══════════════════════════════════════ ─ □ ✕ ┐
│                                                            │
│                                                            │
│            No database queries recorded                    │
│            for this request.                               │
│                                                            │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

### Minimized State

```
┌══ DB Queries (8) ═══════════════════════════════════════════════════════════════ ─ □ ✕ ┐
```

A minimized widget collapses to its title bar only, showing the title and item count.
Click the minimize button again or double-click the title bar to restore.

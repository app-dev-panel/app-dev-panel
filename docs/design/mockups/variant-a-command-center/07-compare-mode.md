# Variant A: Command Center — Compare Mode

## Activation

Compare mode is activated by:
1. Clicking "Compare" button in context bar
2. Selecting 2 entries via checkboxes in Debug List and clicking "Compare"
3. Keyboard shortcut Ctrl+Shift+C (opens picker if no second entry selected)

## Full Layout — Side-by-Side

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  ⊞ COMPARE MODE          Entry A vs Entry B                                              [✕ Exit Compare]  ⌘K     │
├────┬────────────────────────────────────────────────────┬────────────────────────────────────────────────────────────┤
│    │ GET 200 /api/users?page=2   87ms  4.1MB  14:23:07 │ GET 200 /api/users?page=2   134ms 6.3MB  14:25:12        │
│ 🔍 ├────────────────────────────────────────────────────┼────────────────────────────────────────────────────────────┤
│    │ Request│Response│Logs│Events│DB│Exc│Prof           │ Request│Response│Logs│Events│DB│Exc│Prof                  │
│ 📋 │ ═══════                                            │ ═══════                                                  │
│    ├────────────────────────────────────────────────────┼────────────────────────────────────────────────────────────┤
│ 🔧 │                                                    │                                                          │
│    │  General                                           │  General                                                 │
│ 📊 │  ───────────────────────────────────               │  ───────────────────────────────────                     │
│    │  Request URL   /api/users?page=2&limit=25          │  Request URL   /api/users?page=2&limit=25                │
│ 📁 │  Method        GET                                 │  Method        GET                                       │
│    │  Status        200 OK                              │  Status        200 OK                                    │
│ 🛠  │  Remote Addr   127.0.0.1:8080                      │  Remote Addr   127.0.0.1:8080                            │
│    │                                                    │                                                          │
│    │  Request Headers                                   │  Request Headers                                         │
│    │  ───────────────────────────────────               │  ───────────────────────────────────                     │
│    │  Accept         application/json                   │  Accept         application/json                         │
│    │  Authorization  Bearer eyJhbGci...                 │  Authorization  Bearer eyJhbGci...                       │
│    │  Content-Type   application/json                   │  Content-Type   application/json                         │
│    │  User-Agent     Mozilla/5.0 (X11; Linux)           │  User-Agent     Mozilla/5.0 (X11; Linux)                 │
│    │                                                    │  X-Custom       new-header-value          ◀── ADDED      │
│    │                                                    │                                                          │
│    │  Query Parameters                                  │  Query Parameters                                        │
│    │  ───────────────────────────────────               │  ───────────────────────────────────                     │
│    │  page           2                                  │  page           2                                        │
│    │  limit          25                                 │  limit          25                                       │
│    │                                                    │                                                          │
├────┴────────────────────────────────────────────────────┴────────────────────────────────────────────────────────────┤
│  Comparing abc123 vs def456                                                ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Diff Highlighting

Differences between entries are highlighted with color-coded backgrounds:

```
Left panel only (removed):                          Right panel only (added):
┌──────────────────────────────────────┐            ┌──────────────────────────────────────┐
│  Cache-Control  no-cache    ◀─ REMOVED│            │  X-Custom  new-header    ◀── ADDED   │
│  ▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒▒ │            │  ░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░ │
└──────────────────────────────────────┘            └──────────────────────────────────────┘
 Red tint background                                 Green tint background

Changed value:
┌──────────────────────────────────────┐            ┌──────────────────────────────────────┐
│  Time          87ms                  │            │  Time          134ms        ◀─ CHANGED│
│                ▒▒▒▒                  │            │                ░░░░░                  │
└──────────────────────────────────────┘            └──────────────────────────────────────┘
 Old value highlighted                               New value highlighted
```

## Compare: DB Queries Tab

```
┌─ DB (8 queries, 34ms) ────────────────────────┬─ DB (11 queries, 78ms) ──────────────────────────────┐
│                                                │                                                      │
│  1. SELECT * FROM "user" WHERE...    12ms      │  1. SELECT * FROM "user" WHERE...    12ms            │
│  2. SELECT COUNT(*) FROM "user"...    3ms      │  2. SELECT COUNT(*) FROM "user"...    3ms            │
│  3. SELECT "r"."name" FROM...         8ms      │  3. SELECT "r"."name" FROM...         8ms            │
│  4. SELECT "key" FROM "setting"...    2ms      │  4. SELECT "key" FROM "setting"...    2ms            │
│  5. SELECT * FROM "cache_item"...     1ms      │  5. SELECT * FROM "cache_item"...     1ms            │
│  6. UPDATE "request_log" SET...       4ms      │  6. UPDATE "request_log" SET...       4ms            │
│  7. SELECT "count" FROM "rate_..."    2ms      │  7. SELECT "count" FROM "rate_..."    2ms            │
│  8. INSERT INTO "audit_log"...        2ms      │  8. INSERT INTO "audit_log"...        2ms            │
│                                                │  9. SELECT * FROM "user_pref"...      5ms  ◀── NEW   │
│                                                │ 10. SELECT * FROM "theme" WHERE...    3ms  ◀── NEW   │
│                                                │ 11. UPDATE "analytics" SET...        35ms  ◀── SLOW  │
│                                                │                                                      │
└────────────────────────────────────────────────┴──────────────────────────────────────────────────────┘
```

## Compare: Summary Panel

At the top of compare mode, a summary shows key metric differences:

```
┌─ Comparison Summary ──────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                   │
│   Metric            Entry A           Entry B           Delta                                                    │
│   ──────────────────────────────────────────────────────────────                                                  │
│   Response Time     87ms              134ms             +47ms  (+54%)  ▲ slower                                   │
│   Memory Usage      4.1 MB            6.3 MB            +2.2 MB (+54%)  ▲ more                                   │
│   DB Queries        8                 11                +3             ▲ more                                     │
│   DB Time           34ms              78ms              +44ms  (+129%) ▲ slower                                   │
│   Events            31                35                +4             ▲ more                                     │
│   Log Entries       5                 8                 +3             ▲ more                                     │
│                                                                                                                   │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

Delta colors: green for improvements, red for regressions. Arrow direction indicates change direction.

## Entry Picker (when entering compare mode)

```
┌─ Select Entry to Compare With ───────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                   │
│  Current: GET 200 /api/users?page=2&limit=25  87ms  14:23:07                                                     │
│                                                                                                                   │
│  🔍 [________________________________]                                                                            │
│                                                                                                                   │
│  ┌────────────────────────────────────────────────────────────────────────────────────────────────────────────┐    │
│  │  Suggested (same URL):                                                                                    │    │
│  │    ○ GET 200 /api/users?page=2&limit=25   134ms  14:25:12                                                │    │
│  │    ○ GET 200 /api/users?page=2&limit=25    92ms  14:18:33                                                │    │
│  │    ○ GET 200 /api/users?page=2&limit=25    88ms  14:10:05                                                │    │
│  │                                                                                                           │    │
│  │  Recent:                                                                                                  │    │
│  │    ○ POST 201 /api/users                  145ms  14:22:58                                                │    │
│  │    ○ GET 404 /api/users/999                23ms  14:22:30                                                │    │
│  │    ○ POST 500 /api/auth/refresh           340ms  14:22:15                                                │    │
│  └────────────────────────────────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                                                   │
│                                                                          [Cancel]   [Compare Selected]            │
│                                                                                                                   │
└───────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

The picker groups entries: first shows entries with the same URL (most useful for regression comparison),
then shows all recent entries.

## Synchronized Scrolling

Both panels scroll together by default. A toggle allows independent scrolling:

```
  ┌──────────────────────────────────────┐
  │  🔗 Sync scroll: [ON]  [OFF]         │
  └──────────────────────────────────────┘
```

When synced, scrolling either panel scrolls both.
When not synced, each panel scrolls independently.

## Tab Synchronization

Clicking a tab in one panel switches both panels to the same tab:

```
  Left: Request│Response│Logs│Events│DB       Right: Request│Response│Logs│Events│DB
       ═══════                                       ═══════
```

Clicking "Logs" on the left also switches the right to "Logs".
A toggle allows independent tab navigation (advanced use case).

## Interaction Notes

- Compare mode adds `?compare=<id>` to URL, making it shareable
- Panels are resizable split panes; drag the center divider
- Keyboard: Tab to switch focus between panels
- Keyboard: Escape exits compare mode
- Diff highlighting computes automatically on tab switch
- Only data from the same collector type is compared (no cross-collector diff)

## State Management

| State                | Storage      | Rationale                                |
|----------------------|-------------|------------------------------------------|
| Compare entry ID     | URL param   | `?compare=def456` — shareable            |
| Active tab (synced)  | URL param   | `?tab=db` — same as normal mode          |
| Sync scroll toggle   | Local state | Transient preference                     |
| Sync tab toggle      | Local state | Transient preference                     |
| Split pane ratio     | localStorage| User preference                          |
| Diff computations    | Redux (memo)| Computed from two entry datasets         |

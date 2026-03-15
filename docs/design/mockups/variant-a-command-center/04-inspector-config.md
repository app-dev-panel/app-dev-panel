# Variant A: Command Center — Inspector: Configuration

## Full Layout

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Inspector — Configuration                                                                       ⌘K Search        │
├────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                               │
│ 🔍 │  Application Configuration                                                                                    │
│    │                                                                                                               │
│ 📋 │  ┌─ Toolbar ──────────────────────────────────────────────────────────────────────────────────────────────┐    │
│    │  │  Search: [__________________________]   Group: [All ▾]   │ Expand All │ Collapse All │  ⤓ Export JSON │    │
│ 🔧 │  └───────────────────────────────────────────────────────────────────────────────────────────────────────┘    │
│    │                                                                                                               │
│ 📊 │  ┌─ Tree ──────────────────────────────────────────┬─ Detail ──────────────────────────────────────────────┐  │
│    │  │                                                  │                                                      │  │
│ 📁 │  │  ▼ app                                           │  Key:   app.name                                    │  │
│    │  │    ├── name: "My Application"          ◀──────── │  Type:  string                                      │  │
│ 🛠  │  │    ├── version: "2.1.0"                         │  Value: "My Application"                            │  │
│    │  │    ├── debug: true                               │                                                      │  │
│    │  │    ├── environment: "development"                 │  Defined in:                                        │  │
│    │  │    └── charset: "UTF-8"                          │  /app/config/params.php:12                           │  │
│    │  │                                                  │                                                      │  │
│    │  │  ▼ db                                            │  ── History ──────────────────                       │  │
│    │  │    ├── dsn: "mysql:host=localhost;..."            │  Current: "My Application"                          │  │
│    │  │    ├── username: "app_user"                       │  Default: "Yii Application"                         │  │
│    │  │    ├── password: "••••••••"                       │                                                      │  │
│    │  │    ├── tablePrefix: "app_"                        │                                                      │  │
│    │  │    └─▶ options (3 items)                          │                                                      │  │
│    │  │       ├── ATTR_ERRMODE: 2                        │                                                      │  │
│    │  │       ├── ATTR_DEFAULT_FETCH_MODE: 2             │                                                      │  │
│    │  │       └── ATTR_EMULATE_PREPARES: false           │                                                      │  │
│    │  │                                                  │                                                      │  │
│    │  │  ▶ cache (collapsed)                             │                                                      │  │
│    │  │  ▶ mailer (collapsed)                            │                                                      │  │
│    │  │  ▶ logger (collapsed)                            │                                                      │  │
│    │  │  ▶ session (collapsed)                           │                                                      │  │
│    │  │  ▶ router (collapsed)                            │                                                      │  │
│    │  │  ▶ aliases (collapsed)                           │                                                      │  │
│    │  │  ▶ middleware (collapsed)                        │                                                      │  │
│    │  │                                                  │                                                      │  │
│    │  └──────────────────────────────────────────────────┴──────────────────────────────────────────────────────┘  │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  GET /api/inspector/config -> 200 OK (18ms)                                ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Tree Node Types

```
Expanded group:
  ▼ db                               <- click to collapse
     ├── dsn: "mysql:host=..."
     ├── username: "app_user"
     └── password: "••••••••"

Collapsed group:
  ▶ cache (5 items)                  <- click to expand, shows item count

Leaf node — string:
     ├── name: "My Application"      <- value in green (accent-success)

Leaf node — number:
     ├── port: 3306                   <- value in blue (accent-info)

Leaf node — boolean true:
     ├── debug: true                  <- value in green (accent-success)

Leaf node — boolean false:
     ├── debug: false                 <- value in red (accent-danger)

Leaf node — null:
     ├── cache: null                  <- value in gray, italic

Leaf node — sensitive:
     ├── password: "••••••••"         <- masked, click to reveal temporarily
```

## Search Behavior

Searching filters the tree to show only matching nodes and their parent path:

```
  Search: [database_______________]

  ▼ db                                 <- parent kept to show context
     ├── dsn: "mysql:host=..."         <- matches "database" in expanded DSN
  ▼ cache
     └── database_driver: "redis"      <- matches "database" in key
```

Non-matching branches are hidden. Match terms are highlighted in the tree.

## Detail Panel

The right panel shows details for the currently selected tree node:

```
┌─ Detail ──────────────────────────────────────────────────────┐
│                                                                │
│  Key:    db.options.ATTR_ERRMODE                              │
│  Type:   integer                                              │
│  Value:  2                                                    │
│  Path:   db > options > ATTR_ERRMODE                          │
│                                                                │
│  Defined in:                                                  │
│  /app/config/db.php:28                                        │
│                                                                │
│  PHP Constant:  PDO::ERRMODE_EXCEPTION                        │
│                                                                │
│  ── Raw JSON ──────────────────────                           │
│  {"ATTR_ERRMODE": 2}                                          │
│                                                                │
│                                  [Copy Path] [Copy Value]     │
│                                                                │
└────────────────────────────────────────────────────────────────┘
```

## Split Pane

The tree (left) and detail (right) are a resizable split pane:

```
  ┌─ Tree ─────────────────────║─ Detail ──────────────────────┐
  │                            ║                               │
  │     (resizable split)      ║       (resizable split)       │
  │                            ║                               │
  └────────────────────────────║───────────────────────────────┘
                               ▲
                               │
                          Drag handle (cursor: col-resize)
                          Default: 50/50 split
```

## Interaction Notes

- Tree nodes: click to select and show detail; double-click to expand/collapse
- Arrow keys navigate the tree (up/down for siblings, left/right for collapse/expand)
- Sensitive values (passwords, tokens): click "reveal" icon, auto-hides after 5s
- Copy buttons in detail panel: copy to clipboard with toast confirmation
- Search: debounced 300ms, highlights matches in yellow
- Group filter dropdown: filters top-level groups (app, db, cache, etc.)

## State Management

| State                | Storage      | Rationale                                |
|----------------------|-------------|------------------------------------------|
| Selected tree node   | URL param   | `?key=db.options.ATTR_ERRMODE` — shareable|
| Expanded nodes       | Local state | Transient UI state                       |
| Search query         | URL param   | `?q=database` — shareable                |
| Group filter         | URL param   | `?group=db` — shareable                  |
| Split pane ratio     | localStorage| User preference                          |
| Config data          | Redux       | Fetched from API, cached                 |

# 04 — Inspector: Configuration Viewer

## Full Layout: Config Tree Left, JSON View Right

The Inspector Config view replaces the standard entry list with a configuration tree.
The content area shows the selected config node's value in JSON or table format.

```
┌──────┬───────────────────────────────┬──────────────────────────────────────────────────────────────────────────────┐
│      │ [🔍 Search config...   ] [x]│  Inspector > Config > params > app                                  [...]  │
│ ┌──┐ ├───────────────────────────────┤──────────────────────────────────────────────────────────────────────────────│
│ │ D│ │                               │                                                                            │
│ └──┘ │  ▼ params                     │  ┌─ params.app ──────────────────────────────────────────────────────────┐  │
│      │    ▼ app                      │  │                                                                      │  │
│ ┌──┐ │     >name              (str) <│  │  Key              │ Value                    │ Type    │ Source       │  │
│ │ I│ │      version           (str)  │  │  ─────────────────┼──────────────────────────┼─────────┼──────────── │  │
│ └──┘ │      debug             (bool) │  │  name              │ "My Application"         │ string  │ params.php  │  │
│      │      charset           (str)  │  │  version            │ "3.2.1"                  │ string  │ params.php  │  │
│ ┌──┐ │    ▶ database                 │  │  debug              │ true                     │ bool    │ params.php  │  │
│ │ C│ │    ▶ mailer                   │  │  charset            │ "UTF-8"                  │ string  │ params.php  │  │
│ └──┘ │    ▶ cache                    │  │  timezone           │ "UTC"                    │ string  │ params.php  │  │
│      │  ▶ di                         │  │                                                                      │  │
│ ┌──┐ │  ▶ routes                     │  └──────────────────────────────────────────────────────────────────────┘  │
│ │ S│ │  ▶ events                     │                                                                            │
│ └──┘ │  ▶ middleware                  │  ┌─ Raw JSON ──────────────────────────────────────────────── [Copy] ────┐  │
│      │  ▶ bootstrap                  │  │  {                                                                    │  │
│      │                               │  │    "name": "My Application",                                          │  │
│      │                               │  │    "version": "3.2.1",                                                │  │
│      │                               │  │    "debug": true,                                                     │  │
│      │                               │  │    "charset": "UTF-8",                                                │  │
│      │                               │  │    "timezone": "UTC"                                                  │  │
│      │                               │  │  }                                                                    │  │
│      │                               │  └────────────────────────────────────────────────────────────────────────┘  │
│      ├───────────────────────────────┤                                                                            │
│ ┌──┐ │ ● Connected                   │                                                                            │
│ │ T│ │ 42 config nodes               │                                                                            │
└──┴──┴───────────────────────────────┴──────────────────────────────────────────────────────────────────────────────┘
```

## Config Tree Panel — Detailed

### Tree Node Types

```
┌───────────────────────────────┐
│                               │
│  ▼ params              (obj)  │  <-- expanded object node (has children)
│    ▼ app               (obj)  │  <-- expanded nested object
│      name              (str)  │  <-- leaf: string value
│      version           (str)  │
│      debug             (bool) │  <-- leaf: boolean value
│      port              (int)  │  <-- leaf: integer value
│      rate              (float)│  <-- leaf: float value
│      tags              (arr)  │  <-- leaf: array value
│    ▶ database          (obj)  │  <-- collapsed object (click to expand)
│    ▶ mailer            (obj)  │
│  ▶ di                  (obj)  │  <-- top-level collapsed
│  ▶ routes              (arr)  │  <-- top-level array
│                               │
└───────────────────────────────┘
```

### Tree Node States

```
Normal:
│    ▶ database          (obj)  │

Hover:
│   [▶ database          (obj)] │  <-- subtle background

Selected:
│▎  >▼ database          (obj)<│  <-- accent bar + highlight

Search match:
│    ▶ da[ta]base        (obj)  │  <-- matched text highlighted
```

### Search Behavior

```
Searching for "host":
┌───────────────────────────────┐
│ [🔍 host                ] [x]│
├───────────────────────────────┤
│                               │
│  ▼ params              (obj)  │  <-- auto-expanded to show matches
│    ▼ database          (obj)  │
│      host              (str)  │  <-- "host" highlighted
│    ▼ mailer            (obj)  │
│      smtp_host         (str)  │  <-- "host" highlighted in key
│    ▼ cache             (obj)  │
│      ▼ redis           (obj)  │
│        host            (str)  │  <-- deep match auto-expanded
│                               │
│  3 matches found              │
└───────────────────────────────┘
```

## Content Area — Table View

### Flat Key-Value Table (for object nodes)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  params.database                                                            │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌──────────────────┬────────────────────────────────┬─────────┬─────────┐ │
│  │ Key              │ Value                          │ Type    │ Source  │ │
│  ├──────────────────┼────────────────────────────────┼─────────┼─────────┤ │
│  │ driver           │ "pgsql"                        │ string  │ db.php  │ │
│  │ host             │ "localhost"                    │ string  │ db.php  │ │
│  │ port             │ 5432                           │ integer │ db.php  │ │
│  │ database         │ "myapp"                        │ string  │ db.php  │ │
│  │ username         │ "app_user"                     │ string  │ .env    │ │
│  │ password         │ "••••••••"                     │ string  │ .env    │ │
│  │ charset          │ "utf8"                         │ string  │ db.php  │ │
│  │ pool_size        │ 10                             │ integer │ db.php  │ │
│  │ ssl_mode         │ "prefer"                       │ string  │ db.php  │ │
│  └──────────────────┴────────────────────────────────┴─────────┴─────────┘ │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Array View (for route definitions)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  routes                                                      12 routes     │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌────┬─────────┬────────────────────┬────────────────────────────┬───────┐ │
│  │ #  │ Method  │ Pattern            │ Handler                    │ Mid.  │ │
│  ├────┼─────────┼────────────────────┼────────────────────────────┼───────┤ │
│  │ 1  │ GET     │ /                  │ HomeController::index      │  3    │ │
│  │ 2  │ GET     │ /api/users         │ UserController::index      │  4    │ │
│  │ 3  │ POST    │ /api/users         │ UserController::store      │  5    │ │
│  │ 4  │ GET     │ /api/users/{id}    │ UserController::show       │  4    │ │
│  │ 5  │ PUT     │ /api/users/{id}    │ UserController::update     │  5    │ │
│  │ 6  │ DELETE  │ /api/users/{id}    │ UserController::destroy    │  5    │ │
│  │ 7  │ GET     │ /dashboard         │ DashboardController::index │  3    │ │
│  │ 8  │ GET     │ /api/health        │ HealthController::check    │  2    │ │
│  │ .. │ ...     │ (4 more routes)    │                            │       │ │
│  └────┴─────────┴────────────────────┴────────────────────────────┴───────┘ │
│                                                                            │
│  Showing 8 of 12                                           [Show all]      │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Content Area — JSON View

### Toggle between Table and JSON

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  params.database                              [Table] [JSON]  [Copy] [...] │
├──────────────────────────────────────────────────────────────────────────────┤
```

### JSON View Mode

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  params.database                              [Table] [JSON]  [Copy] [...] │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌────────────────────────────────────────────────────────────────────────┐ │
│  │  1  {                                                                 │ │
│  │  2    "driver": "pgsql",                                              │ │
│  │  3    "host": "localhost",                                            │ │
│  │  4    "port": 5432,                                                   │ │
│  │  5    "database": "myapp",                                            │ │
│  │  6    "username": "app_user",                                         │ │
│  │  7    "password": "********",                                         │ │
│  │  8    "charset": "utf8",                                              │ │
│  │  9    "pool_size": 10,                                                │ │
│  │ 10    "ssl_mode": "prefer"                                            │ │
│  │ 11  }                                                                 │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                            │
│  Syntax highlighting: keys in blue, strings in green,                      │
│  numbers in orange, booleans in purple, null in gray                       │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Breadcrumb Navigation

Clicking any segment navigates to that node in the tree:

```
  Inspector > Config > params > database > host
  ─────────   ──────   ──────   ────────   ────
  (module)    (root)   (node)   (node)    (leaf)

  Each segment is clickable. Clicking "params" selects the params node
  in the tree and shows its children in the content area.
```

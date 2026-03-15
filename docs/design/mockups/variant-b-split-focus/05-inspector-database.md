# 05 — Inspector: Database

## Full Layout: Table List Left, Query Results Right

The Inspector Database view shows database tables in the left panel and
query results / table structure in the content area.

```
┌──────┬───────────────────────────────┬──────────────────────────────────────────────────────────────────────────────┐
│      │ [🔍 Search tables...   ] [x]│  Inspector > Database > users                                       [...]  │
│ ┌──┐ ├───────────────────────────────┤──────────────────────────────────────────────────────────────────────────────│
│ │ D│ │                               │  ┌─────────────────────────────────────────────────────────────────────────┐│
│ └──┘ │  Tables              (12)     │  │ SELECT * FROM users ORDER BY id DESC LIMIT 50                    [Run]││
│      │  ─────────────────────────    │  └─────────────────────────────────────────────────────────────────────────┘│
│ ┌──┐ │ >users               (1,247)<│                                                                            │
│ │ I│ │  roles                  (8)   │  [Results] [Structure] [Indexes] [SQL]                 Rows: 50 of 1,247  │
│ └──┘ │  user_roles           (892)   │  ──────────────────────────────────────────────────────────────────────────│
│      │  permissions           (24)   │                                                                            │
│ ┌──┐ │  posts              (3,891)   │  ┌────┬──────────────┬───────────────────┬────────────┬────────────────────┐│
│ │ C│ │  comments           (8,234)   │  │ id │ name         │ email             │ created_at │ status             ││
│ └──┘ │  categories            (15)   │  ├────┼──────────────┼───────────────────┼────────────┼────────────────────┤│
│      │  tags                  (42)   │  │ 42 │ John Doe     │ john@example.com  │ 2026-03-15 │ active             ││
│ ┌──┐ │  post_tags          (1,205)   │  │ 41 │ Jane Smith   │ jane@example.com  │ 2026-03-14 │ active             ││
│ │ S│ │  sessions             (156)   │  │ 40 │ Bob Wilson   │ bob@example.com   │ 2026-03-14 │ inactive           ││
│ └──┘ │  migrations            (23)   │  │ 39 │ Alice Brown  │ alice@example.com │ 2026-03-13 │ active             ││
│      │  settings              (48)   │  │ 38 │ Charlie Fox  │ charlie@examp...  │ 2026-03-13 │ pending            ││
│      │                               │  │ 37 │ Diana Prince │ diana@example.com │ 2026-03-12 │ active             ││
│      │                               │  │ .. │ ...          │ ...               │ ...        │ ...                ││
│      │                               │  └────┴──────────────┴───────────────────┴────────────┴────────────────────┘│
│      │                               │                                                                            │
│      │                               │  ◀ 1 2 3 4 ... 25 ▶                                   50 rows per page    │
│      ├───────────────────────────────┤                                                                            │
│ ┌──┐ │ ● Connected                   │                                                                            │
│ │ T│ │ 12 tables                     │                                                                            │
└──┴──┴───────────────────────────────┴──────────────────────────────────────────────────────────────────────────────┘
```

## Table List Panel

### Table Entry Format

```
┌───────────────────────────────┐
│                               │
│  Tables              (12)     │
│  ─────────────────────────    │
│  users               (1,247)  │  <-- table name + row count
│  roles                  (8)   │
│  user_roles           (892)   │
│  permissions           (24)   │
│  posts              (3,891)   │
│  comments           (8,234)   │  <-- largest table, highest count
│  categories            (15)   │
│  tags                  (42)   │
│  post_tags          (1,205)   │
│  sessions             (156)   │
│  migrations            (23)   │
│  settings              (48)   │
│                               │
└───────────────────────────────┘
```

### Table Context Menu (Right-Click)

```
                  ┌───────────────────────────┐
  users    (1247) │  View structure           │
  roles       (8) │  Browse data              │
 >user_roles(892) │  Export as CSV            │
  permissions (24)│  Export as SQL            │
                  │  ─────────────────────────│
                  │  Copy table name          │
                  │  Copy SELECT query        │
                  │  Count rows               │
                  └───────────────────────────┘
```

## Content Area — Query Editor + Results

### Query Input Bar

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ SELECT * FROM users ORDER BY id DESC LIMIT 50                        [Run] │
│                                                                  Ctrl+Enter │
└──────────────────────────────────────────────────────────────────────────────┘
```

Query editor supports:
- Multi-line SQL (auto-expands)
- Syntax highlighting
- Ctrl+Enter to execute
- Query history (up/down arrows when focused)

### Tab Bar

```
[Results] [Structure] [Indexes] [SQL]                 Rows: 50 of 1,247
```

### Results Tab — Data Grid

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  [Results] [Structure] [Indexes] [SQL]               Rows: 50 of 1,247     │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌────┬──────────────┬───────────────────┬──────────────────┬─────────────┐ │
│  │ id │ name     [▾] │ email             │ created_at   [▴] │ status      │ │
│  ├────┼──────────────┼───────────────────┼──────────────────┼─────────────┤ │
│  │ 42 │ John Doe     │ john@example.com  │ 2026-03-15 14:23 │ active      │ │
│  │ 41 │ Jane Smith   │ jane@example.com  │ 2026-03-14 09:15 │ active      │ │
│  │ 40 │ Bob Wilson   │ bob@example.com   │ 2026-03-14 08:42 │ inactive    │ │
│  │ 39 │ Alice Brown  │ alice@example.com │ 2026-03-13 16:30 │ active      │ │
│  │ 38 │ Charlie Fox  │ charlie@examp...  │ 2026-03-13 11:20 │ pending     │ │
│  │ 37 │ Diana Prince │ diana@example.com │ 2026-03-12 22:08 │ active      │ │
│  │ 36 │ Eve Adams    │ eve@example.com   │ 2026-03-12 18:55 │ active      │ │
│  │ 35 │ Frank Ocean  │ frank@example.com │ 2026-03-11 14:12 │ suspended   │ │
│  └────┴──────────────┴───────────────────┴──────────────────┴─────────────┘ │
│                                                                            │
│  ◀ 1 [2] 3 4 ... 25 ▶                               [50 ▾] rows per page │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Structure Tab

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  [Results] [Structure] [Indexes] [SQL]                            users    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌────┬──────────────┬──────────────────┬──────────┬─────────┬───────────┐ │
│  │ #  │ Column       │ Type             │ Nullable │ Default │ Key       │ │
│  ├────┼──────────────┼──────────────────┼──────────┼─────────┼───────────┤ │
│  │ 1  │ id           │ integer          │ NO       │ (auto)  │ PRIMARY   │ │
│  │ 2  │ name         │ varchar(255)     │ NO       │ -       │ -         │ │
│  │ 3  │ email        │ varchar(255)     │ NO       │ -       │ UNIQUE    │ │
│  │ 4  │ password     │ varchar(255)     │ NO       │ -       │ -         │ │
│  │ 5  │ status       │ varchar(20)      │ NO       │ 'pending│ -         │ │
│  │ 6  │ avatar_url   │ varchar(512)     │ YES      │ NULL    │ -         │ │
│  │ 7  │ last_login   │ timestamp        │ YES      │ NULL    │ -         │ │
│  │ 8  │ created_at   │ timestamp        │ NO       │ now()   │ -         │ │
│  │ 9  │ updated_at   │ timestamp        │ NO       │ now()   │ -         │ │
│  └────┴──────────────┴──────────────────┴──────────┴─────────┴───────────┘ │
│                                                                            │
│  9 columns  |  Engine: InnoDB  |  Collation: utf8mb4_unicode_ci            │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

### Indexes Tab

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  [Results] [Structure] [Indexes] [SQL]                            users    │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌──────────────────────┬───────────┬─────────────────┬──────────┬───────┐ │
│  │ Index Name           │ Type      │ Columns         │ Unique   │ Card. │ │
│  ├──────────────────────┼───────────┼─────────────────┼──────────┼───────┤ │
│  │ PRIMARY              │ BTREE     │ id              │ Yes      │ 1,247 │ │
│  │ users_email_unique   │ BTREE     │ email           │ Yes      │ 1,247 │ │
│  │ users_status_idx     │ BTREE     │ status          │ No       │     4 │ │
│  │ users_created_idx    │ BTREE     │ created_at      │ No       │   312 │ │
│  └──────────────────────┴───────────┴─────────────────┴──────────┴───────┘ │
│                                                                            │
│  4 indexes                                                                 │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

### SQL Tab (Table Creation SQL)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  [Results] [Structure] [Indexes] [SQL]                   [Copy]    users   │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌────────────────────────────────────────────────────────────────────────┐ │
│  │  CREATE TABLE users (                                                 │ │
│  │      id INTEGER PRIMARY KEY AUTOINCREMENT,                            │ │
│  │      name VARCHAR(255) NOT NULL,                                      │ │
│  │      email VARCHAR(255) NOT NULL UNIQUE,                              │ │
│  │      password VARCHAR(255) NOT NULL,                                  │ │
│  │      status VARCHAR(20) NOT NULL DEFAULT 'pending',                   │ │
│  │      avatar_url VARCHAR(512),                                         │ │
│  │      last_login TIMESTAMP,                                            │ │
│  │      created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,         │ │
│  │      updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP          │ │
│  │  );                                                                   │ │
│  │                                                                       │ │
│  │  CREATE UNIQUE INDEX users_email_unique ON users (email);             │ │
│  │  CREATE INDEX users_status_idx ON users (status);                     │ │
│  │  CREATE INDEX users_created_idx ON users (created_at);                │ │
│  └────────────────────────────────────────────────────────────────────────┘ │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Row Detail Overlay (Click a row in Results)

```
┌──────────────────────────────────────────────────────────────────────────────┐
│  Row Detail — users #42                                           [Close]  │
├──────────────────────────────────────────────────────────────────────────────┤
│                                                                            │
│  ┌──────────────────┬────────────────────────────────────────────────────┐  │
│  │ id               │ 42                                                │  │
│  │ name             │ John Doe                                          │  │
│  │ email            │ john@example.com                                  │  │
│  │ password         │ $2y$12$LJ3m4cY4Gj...                             │  │
│  │ status           │ active                                            │  │
│  │ avatar_url       │ https://cdn.example.com/avatars/42.jpg            │  │
│  │ last_login       │ 2026-03-15 14:23:07                               │  │
│  │ created_at       │ 2026-03-15 14:23:07                               │  │
│  │ updated_at       │ 2026-03-15 14:23:07                               │  │
│  └──────────────────┴────────────────────────────────────────────────────┘  │
│                                                                            │
│  [Copy as JSON]  [Copy as PHP array]  [Copy INSERT]                        │
│                                                                            │
└──────────────────────────────────────────────────────────────────────────────┘
```

## Query History

Accessible via up/down arrow keys in query input:

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ SELECT * FROM users WHERE status = 'active' ORDER BY created_at DESC [Run] │
├──────────────────────────────────────────────────────────────────────────────┤
│  Recent Queries:                                                           │
│  ┌────────────────────────────────────────────────────────────┬───────────┐ │
│  │ SELECT * FROM users ORDER BY id DESC LIMIT 50             │ 12ms  50r │ │
│  │ SELECT * FROM users WHERE status = 'active'               │  8ms  32r │ │
│  │ SELECT COUNT(*) FROM users GROUP BY status                │  3ms   4r │ │
│  │ SELECT u.*, r.name FROM users u JOIN roles r ON...        │ 45ms 120r │ │
│  │ EXPLAIN SELECT * FROM users WHERE email LIKE '%@gm...'    │  1ms   3r │ │
│  └────────────────────────────────────────────────────────────┴───────────┘ │
│  5 recent queries                                             [Clear all]  │
└──────────────────────────────────────────────────────────────────────────────┘
```

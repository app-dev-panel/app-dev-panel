# Variant A: Command Center — Inspector: Database

## Full Layout

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Inspector — Database                                                                            ⌘K Search        │
├────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                               │
│ 🔍 │  Database Browser                                    Connection: [default (mysql) ▾]                           │
│    │                                                                                                               │
│ 📋 │  ┌─ Tables ──────────────┬─ Table: users ─────────────────────────────────────────────────────────────────┐    │
│    │  │                       │                                                                                │    │
│ 🔧 │  │  🔍 [_____________]   │  Structure │ Data │ SQL │ Indexes │ Foreign Keys │                              │    │
│    │  │                       │  ═════════                                                                      │    │
│ 📊 │  │  ● users         (142)│                                                                                │    │
│    │  │    roles          (8) │  ┌────║──────────────║──────────║──────────║──────────║─────────║──────────────┐ │    │
│ 📁 │  │    permissions   (24) │  │ #  ║ Column       ║ Type     ║ Nullable ║ Default  ║ Key     ║ Extra        │ │    │
│    │  │    user_roles    (350)│  ├────║──────────────║──────────║──────────║──────────║─────────║──────────────┤ │    │
│ 🛠  │  │    products     (500)│  │ 1  ║ id           ║ int(11)  ║ NO       ║ —        ║ PRI     ║ auto_incr    │ │    │
│    │  │    categories    (15) │  │ 2  ║ email        ║ varchar  ║ NO       ║ —        ║ UNI     ║              │ │    │
│    │  │    orders        (1.2k)│  │    ║              ║ (255)    ║          ║          ║         ║              │ │    │
│    │  │    order_items   (3.4k)│  │ 3  ║ name         ║ varchar  ║ NO       ║ —        ║         ║              │ │    │
│    │  │    settings      (42) │  │    ║              ║ (100)    ║          ║          ║         ║              │ │    │
│    │  │    migrations    (28) │  │ 4  ║ password_hash║ varchar  ║ NO       ║ —        ║         ║              │ │    │
│    │  │    cache_items   (89) │  │    ║              ║ (255)    ║          ║          ║         ║              │ │    │
│    │  │    audit_log    (5.6k)│  │ 5  ║ active       ║ tinyint  ║ NO       ║ 1        ║         ║              │ │    │
│    │  │    rate_limits   (200)│  │    ║              ║ (1)      ║          ║          ║         ║              │ │    │
│    │  │    sessions      (45) │  │ 6  ║ role         ║ enum     ║ NO       ║ 'user'   ║ IDX     ║              │ │    │
│    │  │                       │  │ 7  ║ created_at   ║ datetime ║ NO       ║ NOW()    ║         ║              │ │    │
│    │  │                       │  │ 8  ║ updated_at   ║ datetime ║ YES      ║ NULL     ║         ║              │ │    │
│    │  │                       │  │ 9  ║ last_login   ║ datetime ║ YES      ║ NULL     ║         ║              │ │    │
│    │  │                       │  │ 10 ║ avatar_url   ║ varchar  ║ YES      ║ NULL     ║         ║              │ │    │
│    │  │                       │  │    ║              ║ (500)    ║          ║          ║         ║              │ │    │
│    │  │                       │  └────║──────────────║──────────║──────────║──────────║─────────║──────────────┘ │    │
│    │  │                       │                                                                                │    │
│    │  └───────────────────────┴────────────────────────────────────────────────────────────────────────────────┘    │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  GET /api/inspector/database/tables -> 200 OK (34ms)                       ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Data Tab (browsing table rows)

```
  Structure │ Data │ SQL │ Indexes │ Foreign Keys │
             ════

  ┌─ Toolbar ────────────────────────────────────────────────────────────────────────────────────────────┐
  │  WHERE: [active = 1____________________________]  LIMIT: [25]  │ ▶ Execute │ Density: ☰ │ ⤓ Export  │
  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘

  ┌─────║────║──────────────────────║──────────────────║────────║──────║─────────────────────║───────────┐
  │  id ▲║ em ║ name            ▲   ║ active           ║ role   ║ crea ║ updated_at          ║ last_login│
  │     ║ ail║                      ║                  ║        ║ ted_ ║                     ║           │
  │     ║    ║                      ║                  ║        ║ at   ║                     ║           │
  ├─────║────║──────────────────────║──────────────────║────────║──────║─────────────────────║───────────┤
  │  26 ║ ali║ Alice Johnson        ║ 1                ║ admin  ║ 2026 ║ 2026-03-15 10:00:00 ║ 2026-03-15│
  │     ║ ce@║                      ║                  ║        ║ -01- ║                     ║  14:23:07 │
  │     ║ ex.║                      ║                  ║        ║ 15   ║                     ║           │
  │  27 ║ bob║ Bob Smith            ║ 1                ║ user   ║ 2026 ║ 2026-03-14 09:15:00 ║ 2026-03-14│
  │     ║ @ex║                      ║                  ║        ║ -01- ║                     ║  16:45:00 │
  │     ║ .co║                      ║                  ║        ║ 20   ║                     ║           │
  │  28 ║ car║ Carol Williams       ║ 1                ║ editor ║ 2026 ║ 2026-03-13 14:22:00 ║ 2026-03-13│
  │     ║ ol@║                      ║                  ║        ║ -02- ║                     ║  18:30:00 │
  │     ║ ex.║                      ║                  ║        ║ 01   ║                     ║           │
  └─────║────║──────────────────────║──────────────────║────────║──────║─────────────────────║───────────┘

  Showing 1-25 of 142 (WHERE active = 1)                              ◀  1  2  3  4  5  6  ▶
```

## SQL Tab (query editor)

```
  Structure │ Data │ SQL │ Indexes │ Foreign Keys │
                    ═══

  ┌─ Query Editor ──────────────────────────────────────────────────────────────────────────────────────┐
  │                                                                                                     │
  │   1  SELECT u.name, u.email, r.name AS role_name                                                   │
  │   2  FROM users u                                                                                   │
  │   3  INNER JOIN user_roles ur ON u.id = ur.user_id                                                 │
  │   4  INNER JOIN roles r ON ur.role_id = r.id                                                       │
  │   5  WHERE u.active = 1                                                                            │
  │   6  ORDER BY u.created_at DESC                                                                    │
  │   7  LIMIT 50;                                                                                     │
  │   8  █                                                                                             │
  │                                                                                                     │
  │                                                    [History ▾]   [Format]   [▶ Execute (Ctrl+Enter)]│
  └─────────────────────────────────────────────────────────────────────────────────────────────────────┘

  ┌─ Results (50 rows, 12ms) ──────────────────────────────────────────────────────────────────────────┐
  │                                                                                                     │
  │  ┌──────────────────────║──────────────────────║──────────────────┐                                 │
  │  │ name                 ║ email                ║ role_name        │                                 │
  │  ├──────────────────────║──────────────────────║──────────────────┤                                 │
  │  │ Alice Johnson        ║ alice@example.com    ║ admin            │                                 │
  │  │ Bob Smith            ║ bob@example.com      ║ user             │                                 │
  │  │ Carol Williams       ║ carol@example.com    ║ editor           │                                 │
  │  │ ...                  ║ ...                  ║ ...              │                                 │
  │  └──────────────────────║──────────────────────║──────────────────┘                                 │
  │                                                                                                     │
  │  50 rows returned in 12ms                                                    [📋 Copy] [⤓ CSV]     │
  │                                                                                                     │
  └─────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Indexes Tab

```
  Structure │ Data │ SQL │ Indexes │ Foreign Keys │
                                ═══════

  ┌──────────────────║──────────────────║──────────────────║──────────────────║──────────────────┐
  │ Name             ║ Columns          ║ Type             ║ Method           ║ Cardinality      │
  ├──────────────────║──────────────────║──────────────────║──────────────────║──────────────────┤
  │ PRIMARY          ║ id               ║ PRIMARY          ║ BTREE            ║ 142              │
  │ idx_email        ║ email            ║ UNIQUE           ║ BTREE            ║ 142              │
  │ idx_role         ║ role             ║ INDEX            ║ BTREE            ║ 3                │
  │ idx_active       ║ active           ║ INDEX            ║ BTREE            ║ 2                │
  │ idx_created      ║ created_at       ║ INDEX            ║ BTREE            ║ 142              │
  └──────────────────║──────────────────║──────────────────║──────────────────║──────────────────┘
```

## Foreign Keys Tab

```
  Structure │ Data │ SQL │ Indexes │ Foreign Keys │
                                    ════════════

  ┌─────────────────║──────────║────────────────║──────────────║──────────────║──────────────────┐
  │ Constraint       ║ Column   ║ References     ║ Ref Column   ║ On Update    ║ On Delete        │
  ├─────────────────║──────────║────────────────║──────────────║──────────────║──────────────────┤
  │ fk_user_role     ║ role_id  ║ roles          ║ id           ║ CASCADE      ║ RESTRICT         │
  └─────────────────║──────────║────────────────║──────────────║──────────────║──────────────────┘

  No foreign keys defined on this table.     <- shown when empty
```

## Table List Panel

Left panel showing database tables:

```
┌─ Tables ──────────────────────┐
│                                │
│  🔍 [_________________]        │
│                                │
│  ● users           (142)      │  <- selected (bullet indicator)
│    roles             (8)      │
│    permissions      (24)      │
│    user_roles      (350)      │
│    ──────────────────────     │  <- visual separator between groups
│    products        (500)      │
│    categories       (15)      │
│    orders         (1.2k)      │
│    order_items    (3.4k)      │
│    ──────────────────────     │
│    settings         (42)      │
│    migrations       (28)      │
│    cache_items      (89)      │
│    audit_log      (5.6k)      │
│    rate_limits     (200)      │
│    sessions         (45)      │
│                                │
│  14 tables, 11.8k total rows  │
│                                │
└────────────────────────────────┘
```

## Interaction Notes

- Table list: click to select, shows structure by default
- Table list search: filters tables by name
- Data tab: WHERE clause is free-form SQL (validated before execution)
- SQL tab: syntax-highlighted editor (CodeMirror or Monaco)
- SQL tab: Ctrl+Enter executes query; query history in dropdown
- SQL tab: read-only mode by default; write queries require explicit opt-in toggle
- Column resize works on all result tables
- Click column name in structure to copy; click row in data to see full cell value
- Foreign key column names are clickable links to the referenced table

## State Management

| State                | Storage      | Rationale                                |
|----------------------|-------------|------------------------------------------|
| Selected table       | URL param   | `?table=users` — bookmarkable            |
| Active tab           | URL param   | `?view=data` — bookmarkable              |
| WHERE clause         | URL param   | `?where=active=1` — shareable            |
| SQL query            | Local state | Transient, but kept in history            |
| Query history        | localStorage| Persists across sessions, per-connection  |
| Connection selection | URL param   | `?conn=default` — shareable              |
| Data page            | URL param   | `?page=2` — bookmarkable                 |
| Table list           | Redux       | Fetched from API, cached                 |
| Query results        | Redux       | Transient, replaced on new query         |
| Split pane ratio     | localStorage| User preference                          |

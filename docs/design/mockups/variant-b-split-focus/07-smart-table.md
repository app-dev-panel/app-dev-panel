# 07 — Smart Table Component

## Base Table — Default State

```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                        Density: [C] [M] [S]    Export: [CSV][JSON]│
├──────┬──────────────────────────────────────────┬───────┬──────┬──────────┬────────────────────────────────────────┤
│ #    │ Query                                ▾▴  │ Time ▾│ Rows │ Status   │ Source                                │
├──────┼──────────────────────────────────────────┼───────┼──────┼──────────┼────────────────────────────────────────┤
│ 1    │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK       │ UserService::create:42                │
│ 2    │ SELECT * FROM roles WHERE active = 1     │  2ms  │   3  │ OK       │ RoleRepository::findActive:18        │
│ 3    │ INSERT INTO user_roles (user_id, rol...) │  2ms  │   3  │ OK       │ UserService::create:56                │
│ 4    │ SELECT * FROM permissions WHERE role...) │  1ms  │  12  │ OK       │ PermissionRepository::forRoles:25    │
│ 5    │ UPDATE users SET last_login = NOW() W... │  3ms  │   1  │ OK       │ AuthService::recordLogin:31          │
│ 6    │ SELECT COUNT(*) FROM sessions WHERE ... │  1ms  │   1  │ OK       │ SessionManager::cleanup:88           │
│ 7    │ DELETE FROM sessions WHERE expires_at... │ 15ms  │  42  │ OK       │ SessionManager::cleanup:92           │
│ 8    │ INSERT INTO audit_log (user_id, acti...) │  2ms  │   1  │ OK       │ AuditService::log:15                 │
├──────┴──────────────────────────────────────────┴───────┴──────┴──────────┴────────────────────────────────────────┤
│ Showing 8 of 8 rows                                                          Total: 34ms   63 rows   0 errors    │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Column Header Interactions

### Sort States (Click to Cycle)

```
Unsorted:        Ascending:       Descending:
│ Time  ▾▴ │     │ Time  ▴  │     │ Time  ▾  │
                  (1st click)      (2nd click)      (3rd click → reset)
```

### Column Filter (Click filter icon in header)

```
┌──────┬──────────────────────────────────────────┬───────┬──────┬──────────┐
│ #    │ Query                                ▾▴  │ Time ▾│ Rows │ Status 🔍│
├──────┼──────────────────────────────────────────┼───────┼──────┼──────────┤
│      │                                          │       │      │┌────────┐│
│      │                                          │       │      ││ Filter ││
│      │                                          │       │      │├────────┤│
│      │  (table rows filtered in real-time)      │       │      ││ ☑ OK   ││
│      │                                          │       │      ││ ☐ WARN ││
│      │                                          │       │      ││ ☑ ERROR││
│      │                                          │       │      │├────────┤│
│      │                                          │       │      ││[Apply] ││
│      │                                          │       │      │└────────┘│
```

### Text Column Filter

```
┌──────────────────────────────────────────┐
│ Query                                🔍  │
├──────────────────────────────────────────┤
│ ┌──────────────────────────────────────┐ │
│ │ INSERT                               │ │  <-- text input, filters as you type
│ └──────────────────────────────────────┘ │
│ ○ Contains  ● Starts with  ○ Regex      │  <-- match mode
├──────────────────────────────────────────┤
```

### Numeric Column Filter

```
┌───────┐
│ Time 🔍│
├───────┤
│┌─────┐│
││Range ││
│├─────┤│
││Min: [│││  <-- numeric range inputs
││    0]││
││Max: [│││
││  100]││
│├─────┤│
││[Apply││
│└─────┘│
```

## Column Resize

```
Before resize:                          During resize:
│ Query                    │ Time │     │ Query                         ┃ Ti│
                                        ← drag handle (cursor: col-resize) →
                                                    ┃ = visible drag indicator
```

## Row Density Modes

### Compact (32px rows) — [C]

```
┌──────┬────────────────────────────────────────┬──────┬─────┬────────┬──────────────────────────────┐
│ #    │ Query                                  │ Time │ Rows│ Status │ Source                       │
├──────┼────────────────────────────────────────┼──────┼─────┼────────┼──────────────────────────────┤
│ 1    │ INSERT INTO users (name, email, c...)  │  8ms │   1 │ OK     │ UserService::create:42       │
│ 2    │ SELECT * FROM roles WHERE active = 1   │  2ms │   3 │ OK     │ RoleRepository::findActive:18│
│ 3    │ INSERT INTO user_roles (user_id, ...)  │  2ms │   3 │ OK     │ UserService::create:56       │
│ 4    │ SELECT * FROM permissions WHERE r...)  │  1ms │  12 │ OK     │ PermissionRepository::for:25 │
│ 5    │ UPDATE users SET last_login = NOW(...) │  3ms │   1 │ OK     │ AuthService::recordLogin:31  │
│ 6    │ SELECT COUNT(*) FROM sessions WHERE... │  1ms │   1 │ OK     │ SessionManager::cleanup:88   │
│ 7    │ DELETE FROM sessions WHERE expires_... │ 15ms │  42 │ OK     │ SessionManager::cleanup:92   │
│ 8    │ INSERT INTO audit_log (user_id, a...)  │  2ms │   1 │ OK     │ AuditService::log:15         │
└──────┴────────────────────────────────────────┴──────┴─────┴────────┴──────────────────────────────┘
```

### Comfortable (48px rows) — [M] — Default

```
┌──────┬────────────────────────────────────────────┬───────┬──────┬──────────┬──────────────────────────┐
│      │                                            │       │      │          │                          │
│  1   │  INSERT INTO users (name, email, creat...) │  8ms  │   1  │  OK      │ UserService::create:42   │
│      │                                            │       │      │          │                          │
├──────┼────────────────────────────────────────────┼───────┼──────┼──────────┼──────────────────────────┤
│      │                                            │       │      │          │                          │
│  2   │  SELECT * FROM roles WHERE active = 1      │  2ms  │   3  │  OK      │ RoleRepository::findA:18 │
│      │                                            │       │      │          │                          │
├──────┼────────────────────────────────────────────┼───────┼──────┼──────────┼──────────────────────────┤
│      │                                            │       │      │          │                          │
│  3   │  INSERT INTO user_roles (user_id, role...) │  2ms  │   3  │  OK      │ UserService::create:56   │
│      │                                            │       │      │          │                          │
└──────┴────────────────────────────────────────────┴───────┴──────┴──────────┴──────────────────────────┘
```

### Spacious (64px rows) — [S]

```
┌──────┬────────────────────────────────────────────┬───────┬──────┬──────────┬──────────────────────────┐
│      │                                            │       │      │          │                          │
│      │                                            │       │      │          │                          │
│  1   │  INSERT INTO users (name, email, creat...) │  8ms  │   1  │  OK      │ UserService::create:42   │
│      │  -- creates a new user record              │       │      │          │                          │
│      │                                            │       │      │          │                          │
├──────┼────────────────────────────────────────────┼───────┼──────┼──────────┼──────────────────────────┤
│      │                                            │       │      │          │                          │
│      │                                            │       │      │          │                          │
│  2   │  SELECT * FROM roles WHERE active = 1      │  2ms  │   3  │  OK      │ RoleRepository::findA:18 │
│      │                                            │       │      │          │                          │
│      │                                            │       │      │          │                          │
└──────┴────────────────────────────────────────────┴───────┴──────┴──────────┴──────────────────────────┘
```

## Row States

### Normal

```
│ 1    │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK       │ UserService::create:42   │
```

### Hover

```
│ 1    │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK       │ UserService::create:42   │
 ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 subtle gray background highlight
```

### Selected

```
│▎1    │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK       │ UserService::create:42   │
 ▎ = left accent bar + primary color background tint
```

### Error Row

```
│ 5    │ SELECT * FROM missing_table               │  1ms  │   0  │ ERROR    │ ReportService::gen:78   │
 ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 red background tint, red status text
```

### Slow Query Row

```
│ 7    │ DELETE FROM sessions WHERE expires_at...  │ 15ms  │  42  │ OK       │ SessionManager::clean:92│
 ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 orange background tint on Time cell (exceeds 10ms threshold)
```

## Keyboard Navigation

```
┌──────────────────────────────────────────────────────────────────┐
│  Key          │  Action                                         │
├───────────────┼─────────────────────────────────────────────────┤
│  Arrow Up     │  Move selection to previous row                 │
│  Arrow Down   │  Move selection to next row                     │
│  Enter        │  Expand selected row (show detail)              │
│  Escape       │  Deselect / collapse expanded row               │
│  Home         │  Jump to first row                              │
│  End          │  Jump to last row                               │
│  Page Up      │  Scroll up one page                             │
│  Page Down    │  Scroll down one page                           │
│  Ctrl+C       │  Copy selected row as text                      │
│  Ctrl+Shift+C │  Copy selected row as JSON                      │
│  Space        │  Toggle row selection (multi-select)            │
└──────────────────────────────────────────────────────────────────┘
```

## Multi-Select Mode

```
┌──────┬──────────────────────────────────────────┬───────┬──────┬──────────┐
│ ☐ #  │ Query                                    │ Time  │ Rows │ Status   │
├──────┼──────────────────────────────────────────┼───────┼──────┼──────────┤
│ ☑ 1  │ INSERT INTO users (name, email, crea...) │  8ms  │   1  │ OK       │  <-- checked
│ ☐ 2  │ SELECT * FROM roles WHERE active = 1     │  2ms  │   3  │ OK       │
│ ☑ 3  │ INSERT INTO user_roles (user_id, rol...) │  2ms  │   3  │ OK       │  <-- checked
│ ☐ 4  │ SELECT * FROM permissions WHERE role...) │  1ms  │  12  │ OK       │
├──────┴──────────────────────────────────────────┴───────┴──────┴──────────┤
│ 2 rows selected                                    [Copy] [Export] [Clear]│
└──────────────────────────────────────────────────────────────────────────────┘
```

## Sticky Header with Scroll

```
┌──────┬──────────────────────────────────────────┬───────┬──────┬──────────┐  <-- sticky (stays visible)
│ #    │ Query                                    │ Time  │ Rows │ Status   │
├──────┼──────────────────────────────────────────┼───────┼──────┼──────────┤
│ ...  │ (scrolled rows above, not visible)       │       │      │          │
│ 15   │ SELECT u.name, COUNT(p.id) FROM user...) │  5ms  │  20  │ OK       │  <-- visible viewport
│ 16   │ INSERT INTO notifications (user_id, ...) │  1ms  │   1  │ OK       │
│ 17   │ UPDATE posts SET view_count = view_co... │  2ms  │   1  │ OK       │
│ 18   │ SELECT * FROM categories ORDER BY name   │  1ms  │  15  │ OK       │
│ 19   │ DELETE FROM cache WHERE key LIKE 'us...' │  8ms  │  23  │ OK       │
│ 20   │ INSERT INTO activity_log (user_id, a...) │  1ms  │   1  │ OK       │
│ ...  │ (scrolled rows below, not visible)       │       │      │          │
└──────┴──────────────────────────────────────────┴───────┴──────┴──────────┘
                                                                          ██  <-- scrollbar
```

## Empty State

```
┌──────┬──────────────────────────────────────────┬───────┬──────┬──────────┐
│ #    │ Query                                    │ Time  │ Rows │ Status   │
├──────┼──────────────────────────────────────────┼───────┼──────┼──────────┤
│      │                                          │       │      │          │
│      │              No queries recorded.        │       │      │          │
│      │                                          │       │      │          │
│      │       This request had no database       │       │      │          │
│      │       interactions.                      │       │      │          │
│      │                                          │       │      │          │
└──────┴──────────────────────────────────────────┴───────┴──────┴──────────┘
```

## Filtered Empty State

```
┌──────┬──────────────────────────────────────────┬───────┬──────┬──────────┐
│ #    │ Query                 [🔍 INSERT]        │ Time  │ Rows │ Status   │
├──────┼──────────────────────────────────────────┼───────┼──────┼──────────┤
│      │                                          │       │      │          │
│      │         No rows match your filter.       │       │      │          │
│      │         [Clear filters]                  │       │      │          │
│      │                                          │       │      │          │
└──────┴──────────────────────────────────────────┴───────┴──────┴──────────┘
```

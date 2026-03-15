# Variant A: Command Center — Inspector: Commands

## Full Layout

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│  Inspector — Commands                                                                            ⌘K Search        │
├────┬─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    │                                                                                                               │
│ 🔍 │  Console Commands                                                          28 commands │  ⤓ Export            │
│    │                                                                                                               │
│ 📋 │  ┌─ Toolbar ──────────────────────────────────────────────────────────────────────────────────────────────┐    │
│    │  │  Search: [__________________________]   Group: [All ▾]   Status: [All ▾]    │ Density: ☰ │             │    │
│ 🔧 │  └───────────────────────────────────────────────────────────────────────────────────────────────────────┘    │
│    │                                                                                                               │
│ 📊 │  ┌──────║────────────────────────────────────║─────────────────────────────────────────║──────────────────┐    │
│    │  │ Name ║ Description                        ║ Arguments                               ║ Group            │    │
│ 📁 │  ├──────║────────────────────────────────────║─────────────────────────────────────────║──────────────────┤    │
│    │  │[____]║ [______________________________]   ║ [___________________________________]   ║ [______________] │    │
│ 🛠  │  ├══════╬════════════════════════════════════╬═════════════════════════════════════════╬══════════════════┤    │
│    │  │      ║                                    ║                                         ║                  │    │
│    │  │ app: ║                                    ║                                         ║                  │    │
│    │  │ ─────║────────────────────────────────────║─────────────────────────────────────────║──────────────────│    │
│    │  │ app: ║ Import users from external         ║ --source=<url> --force --dry-run        ║ app              │    │
│    │  │ impo ║ API source                         ║                                         ║                  │    │
│    │  │ rt-u ║                                    ║                                         ║                  │    │
│    │  │ sers ║                                    ║                                         ║                  │    │
│    │  │      ║                                    ║                                         ║                  │    │
│    │  │ app: ║ Generate monthly analytics         ║ --month=<YYYY-MM> --format=<csv|json>   ║ app              │    │
│    │  │ repo ║ report                             ║                                         ║                  │    │
│    │  │ rt   ║                                    ║                                         ║                  │    │
│    │  │      ║                                    ║                                         ║                  │    │
│    │  │ cach ║                                    ║                                         ║                  │    │
│    │  │ ─────║────────────────────────────────────║─────────────────────────────────────────║──────────────────│    │
│    │  │ cach ║ Clear application cache            ║ --pool=<name> --tag=<tag>               ║ cache            │    │
│    │  │ e:cl ║                                    ║                                         ║                  │    │
│    │  │ ear  ║                                    ║                                         ║                  │    │
│    │  │      ║                                    ║                                         ║                  │    │
│    │  │ cach ║ Warm up cache for given            ║ --pool=<name>                           ║ cache            │    │
│    │  │ e:wa ║ pool                               ║                                         ║                  │    │
│    │  │ rmup ║                                    ║                                         ║                  │    │
│    │  │      ║                                    ║                                         ║                  │    │
│    │  │ db:  ║                                    ║                                         ║                  │    │
│    │  │ ─────║────────────────────────────────────║─────────────────────────────────────────║──────────────────│    │
│    │  │ db:  ║ Run pending database               ║ --force --step=<n>                      ║ database         │    │
│    │  │ migr ║ migrations                         ║                                         ║                  │    │
│    │  │ ate  ║                                    ║                                         ║                  │    │
│    │  │      ║                                    ║                                         ║                  │    │
│    │  └──────║────────────────────────────────────║─────────────────────────────────────────║──────────────────┘    │
│    │                                                                                                               │
├────┴─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│  GET /api/inspector/commands -> 200 OK (22ms)                              ● SSE Connected │ ADP v1.2.0 │  ⚙     │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Command Detail (click a row to expand)

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                                                │
│  ▼ app:import-users                                                                            [▶ Run]         │
│                                                                                                                │
│  ┌─ Details ──────────────────────────────────────────────────────────────────────────────────────────────────┐ │
│  │                                                                                                          │ │
│  │  Description:  Import users from external API source                                                     │ │
│  │  Group:        app                                                                                       │ │
│  │  Class:        App\Command\ImportUsersCommand                                                             │ │
│  │  Hidden:       No                                                                                        │ │
│  │                                                                                                          │ │
│  │  Arguments:                                                                                              │ │
│  │  ┌───────────┬──────────┬──────────┬───────────────────────────────────────────────┐                     │ │
│  │  │ Name      │ Required │ Default  │ Description                                   │                     │ │
│  │  ├───────────┼──────────┼──────────┼───────────────────────────────────────────────┤                     │ │
│  │  │ --source  │ Yes      │ —        │ URL of the external API                       │                     │ │
│  │  │ --force   │ No       │ false    │ Skip confirmation prompts                     │                     │ │
│  │  │ --dry-run │ No       │ false    │ Simulate without making changes               │                     │ │
│  │  │ --limit   │ No       │ 1000     │ Maximum users to import                       │                     │ │
│  │  └───────────┴──────────┴──────────┴───────────────────────────────────────────────┘                     │ │
│  │                                                                                                          │ │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
│                                                                                                                │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

## Run Command Dialog

Clicking [Run] opens an inline run form below the detail:

```
│  ┌─ Run Command ──────────────────────────────────────────────────────────────────────────────────────────┐ │
│  │                                                                                                      │ │
│  │  $ app:import-users                                                                                  │ │
│  │                                                                                                      │ │
│  │  --source  [https://api.example.com/users_________]   (required)                                     │ │
│  │  --force   [✓]                                                                                       │ │
│  │  --dry-run [✓]                                                                                       │ │
│  │  --limit   [100________________________________]                                                     │ │
│  │                                                                                                      │ │
│  │  Preview:  app:import-users --source=https://api.example.com/users --force --dry-run --limit=100     │ │
│  │                                                                                                      │ │
│  │                                                               [Cancel]   [▶ Execute]                 │ │
│  │                                                                                                      │ │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
```

## Run Output

After execution, the output area appears:

```
│  ┌─ Output ───────────────────────────────────────────────────────────────────────────────────────────────┐ │
│  │                                                                                                      │ │
│  │  $ app:import-users --source=https://api.example.com/users --force --dry-run --limit=100             │ │
│  │                                                                                                      │ │
│  │  ┌───────────────────────────────────────────────────────────────────────────────────────────────┐    │ │
│  │  │  [DRY RUN] Starting user import...                                                          │    │ │
│  │  │  Fetching users from https://api.example.com/users                                          │    │ │
│  │  │  Found 847 users in remote API                                                              │    │ │
│  │  │  Processing batch 1/9 (100 users)...                                                        │    │ │
│  │  │    ✓ 98 users would be created                                                              │    │ │
│  │  │    ~ 2 users would be updated (email changed)                                               │    │ │
│  │  │  [DRY RUN] No changes were made.                                                            │    │ │
│  │  │                                                                                             │    │ │
│  │  │  Summary:                                                                                   │    │ │
│  │  │    New:      98                                                                             │    │ │
│  │  │    Updated:  2                                                                              │    │ │
│  │  │    Skipped:  0                                                                              │    │ │
│  │  │    Errors:   0                                                                              │    │ │
│  │  │                                                                                             │    │ │
│  │  │  Exit code: 0                                                                               │    │ │
│  │  │  Duration:  1.23s                                                                           │    │ │
│  │  └───────────────────────────────────────────────────────────────────────────────────────────────┘    │ │
│  │                                                                                                      │ │
│  │  ┌──────────┐  ┌──────────────┐  ┌──────────────┐                                                   │ │
│  │  │ ▶ Re-run │  │ 📋 Copy Output│  │ ⤓ Download  │                                                   │ │
│  │  └──────────┘  └──────────────┘  └──────────────┘                                                   │ │
│  │                                                                                                      │ │
│  └──────────────────────────────────────────────────────────────────────────────────────────────────────┘ │
```

## Output States

Running (live output streaming):
```
│  ┌───────────────────────────────────────────────────────────────────────────────────────────────┐    │
│  │  [DRY RUN] Starting user import...                                                          │    │
│  │  Fetching users from https://api.example.com/users                                          │    │
│  │  █                                                                                          │    │
│  └───────────────────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                                      │
│  ◐ Running... (2.3s elapsed)                                                       [■ Cancel]        │
```

Error output:
```
│  ┌───────────────────────────────────────────────────────────────────────────────────────────────┐    │
│  │  Starting migration...                                                                      │    │
│  │  Applying m240101_120000_create_users_table...                                              │    │
│  │                                                                                             │    │
│  │  ERROR: SQLSTATE[42S01]: Base table or view already exists:                                 │    │
│  │  1050 Table 'users' already exists                                                          │    │
│  │                                                                                             │    │
│  │  Exit code: 1                                                                               │    │
│  │  Duration:  0.45s                                                                           │    │
│  └───────────────────────────────────────────────────────────────────────────────────────────────┘    │
│                                                                                                      │
│  ✗ Command failed with exit code 1                                                                   │
```

## Interaction Notes

- Table rows are grouped by command prefix (app:, cache:, db:, etc.) with group headers
- Click row to expand/collapse inline detail panel
- [Run] button only appears for commands marked as runnable via the API
- Command output streams via SSE, rendered in a terminal-like monospace block
- ANSI color codes in output are converted to styled spans
- Ctrl+C in the output area sends a cancel signal to the running command

## State Management

| State                | Storage      | Rationale                                |
|----------------------|-------------|------------------------------------------|
| Search query         | URL param   | `?q=import` — shareable                  |
| Group filter         | URL param   | `?group=app` — shareable                 |
| Expanded command     | URL param   | `?cmd=app:import-users` — shareable      |
| Run form values      | Local state | Transient, lost on navigation            |
| Command output       | Redux       | Streamed, kept in memory during session  |
| Commands list        | Redux       | Fetched from API, cached                 |

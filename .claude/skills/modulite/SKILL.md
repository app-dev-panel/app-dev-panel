---
name: modulite
description: Manage module boundaries and dependency rules. Run validation, add new modules, update dependency declarations, diagnose violations. Use when adding cross-module imports or creating new libs.
argument-hint: "[check | add-dep <module> <dependency> | add-module <name> | diagnose]"
allowed-tools: Read, Edit, Write, Grep, Glob, Bash
---

# Modulite — Module Boundary Manager

Inspired by VK Modulite (https://github.com/VKCOM/modulite).

Action: $ARGUMENTS

If no argument — run `check`.

## Configuration

- **Rules**: `modulite.php` — module definitions (namespace, path, requires)
- **Validator**: `tools/modulite-check.php` — scans `use` statements, reports violations

## Current Dependency Graph

```
Kernel  (foundation, no internal deps)
  ^
  |
McpServer --> Kernel
  ^
  |
API ---------> Kernel, McpServer
  ^
  |
Cli ---------> Kernel, API, McpServer

Testing  (independent, no internal deps)

Adapter/Yii3 -----> Kernel, API, Cli, McpServer
Adapter/Symfony ---> Kernel, API, Cli, McpServer
Adapter/Laravel ---> Kernel, API, Cli, McpServer
Adapter/Yii2 -----> Kernel, API, Cli, McpServer
Adapter/Cycle -----> API
```

## Actions

### `check` (default)

Run the modulite boundary validator:

```bash
php tools/modulite-check.php
```

If violations found, analyze each one:
1. Is it a legitimate new dependency? -> `add-dep`
2. Is it an architecture violation? -> suggest refactoring
3. Is it a misplaced class? -> suggest moving it

### `add-dep <module> <dependency>`

Add a dependency declaration to `modulite.php`.

Steps:
1. Read `modulite.php`
2. Find the module entry
3. Add the dependency to `requires` array
4. Run `php tools/modulite-check.php` to verify
5. Update CLAUDE.md dependency table if it exists

Validation rules before adding:
- **No circular dependencies** — verify the new edge doesn't create a cycle
- **No adapter-to-adapter** — adapters must NEVER depend on other adapters
- **Kernel stays pure** — kernel cannot require any other internal module
- **Testing stays independent** — testing cannot require internal modules

### `add-module <name>`

Register a new module in `modulite.php`.

Steps:
1. Ask for: namespace, path (or array of paths), required modules
2. Add entry to `modulite.php`
3. Run `php tools/modulite-check.php` to verify
4. Update CLAUDE.md dependency table

### `diagnose`

Deep analysis of current module health:

```bash
# Run check with JSON output for parsing
php tools/modulite-check.php --format=json
```

Then analyze:
1. **Violations** — list and categorize each
2. **Dependency weight** — count internal refs per module pair
3. **Potential issues** — modules with too many dependencies
4. **Suggestions** — possible simplifications

## Architecture Rules (Hard Constraints)

These rules must NEVER be violated:

1. **Acyclic** — no circular dependencies between modules
2. **Kernel is foundation** — depends on nothing internal
3. **Testing is independent** — depends on nothing internal
4. **Adapters never cross-depend** — adapter-symfony cannot import from adapter-laravel
5. **Layer order** — Kernel < McpServer < API < Cli < Adapters
6. **External deps are free** — vendor namespaces are not governed

## Output

After any action, always run the check and report results:

```
Modulite: X modules, Y files, Z internal refs, N violations
```

If violations exist, show them as a table:

| Module | File | Line | Imports | From Module | Status |
|--------|------|------|---------|-------------|--------|

Status: `VIOLATION` (undeclared), `OK` (declared), `BLOCKED` (architectural rule prevents it)

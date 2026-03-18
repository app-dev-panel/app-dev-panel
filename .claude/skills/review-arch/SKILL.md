---
name: review-arch
description: Check architecture compliance after code changes. Detects dependency violations between modules, abstraction leaks, and circular dependencies. Use after any feature implementation.
argument-hint: "[module to review]"
allowed-tools: Read, Grep, Glob, Bash
---

# Architecture Reviewer

Review architecture compliance for: $ARGUMENTS

If no argument — review all modules.

## Dependency Rules

```
Adapter ──▶ API ──▶ Kernel
   │                   ▲
   └───────────────────┘
Cli ──▶ API ──▶ Kernel
Frontend ──▶ API (HTTP only)
```

| Module | Can depend on | CANNOT depend on |
|--------|--------------|------------------|
| Kernel | PSR interfaces, generic PHP libs | API, Cli, Adapter, any framework |
| API | Kernel, PSR interfaces | Adapter, Cli |
| Cli | Kernel, API, Symfony Console | Adapter |
| Adapter/Yiisoft | Kernel, API, Cli, Yii 3 | Other adapters |
| Adapter/Cycle | API, Cycle ORM packages | Other adapters |
| Adapter/Symfony | Kernel, API, Cli, Symfony packages | Other adapters |
| Adapter/Yii2 | Kernel, API, Cli, Yii 2 packages | Other adapters |
| Frontend | Nothing (HTTP only) | Any PHP package |

## Checks

### 1. Import Violations
Scan `use` statements:

```bash
# Kernel must NOT import from API/Cli/Adapter
grep -rn "use AppDevPanel\\\\Api\\\\" libs/Kernel/src/
grep -rn "use AppDevPanel\\\\Cli\\\\" libs/Kernel/src/
grep -rn "use AppDevPanel\\\\Adapter\\\\" libs/Kernel/src/

# API must NOT import from Cli/Adapter
grep -rn "use AppDevPanel\\\\Cli\\\\" libs/API/src/
grep -rn "use AppDevPanel\\\\Adapter\\\\" libs/API/src/

# Cli must NOT import from Adapter
grep -rn "use AppDevPanel\\\\Adapter\\\\" libs/Cli/src/
```

### 2. Composer Violations
Check each module's `composer.json` `require` against rules.

### 3. Abstraction Leaks
- Storage behind `StorageInterface` — no direct `FileStorage` outside Kernel.
- Serialization through `Dumper` — no framework serializers.
- Database via `SchemaProviderInterface` — no direct ORM in controllers.
- Commands via `CommandInterface` — no direct process exec in controllers.

### 4. Circular Dependencies
Detect circular `use` chains between modules.

## Output

Report as table:

| Violation | File | Line | Rule | Fix |
|-----------|------|------|------|-----|

If clean: "No architecture violations detected."

Do NOT auto-fix. Report only — architecture changes need human approval.

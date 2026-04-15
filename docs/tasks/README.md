# Backend Review — Tasks Backlog

Backlog from the full backend review (2026-04-15) covering `libs/Kernel`, `libs/API`, `libs/Cli`, `libs/McpServer`, `libs/Adapter/*`, `libs/Testing`.

Review inputs: `make modulite` (0 violations), `make mago` (pass + 219 baseline warnings), `phpunit` (2813 unit tests, 0 failures, 65 + 97 deprecations), manual architecture / docs / security / simplify audit.

## Priority Index

| # | File | Priority | Area | Title |
|---|------|----------|------|-------|
| P1 | [`p1-security.md`](p1-security.md) | Critical | Security | Secure-by-default auth, CORS, allowlists, path checks, SQL fallback |
| P2 | [`p2-code-quality.md`](p2-code-quality.md) | High | Refactor + Mago | `LlmController` split + helpers, fix mago warnings, PHPUnit attributes migration |
| P3 | [`p3-docs.md`](p3-docs.md) | Medium | Documentation | Sync CLAUDE.md files with actual code (Kernel, API, Laravel) |
| P4 | [`p4-debt.md`](p4-debt.md) | Low | Tech debt | Baseline regen, Cli coverage, Yiisoft dependency review |

## Workflow

1. Pick a task file.
2. Work top-down inside it.
3. Mark task item with `[x]` when done.
4. Run the post-feature pipeline (`make fix && make test && make modulite`) before closing a task.
5. Commit per task section.

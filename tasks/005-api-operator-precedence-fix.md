# Task 005: Fix operator precedence bug in all Command classes

## Module
API (`libs/API/src/Inspector/Command/`)

## Issue (from module review)

### Critical bug: FAIL branch never taken (P0)
`!$process->getExitCode() > 1` is parsed as `(!$process->getExitCode()) > 1`
which is `(bool) > 1` — always `false`.

Affects:
- `BashCommand.php:39`
- `PHPUnitCommand.php:59`
- `CodeceptionCommand.php:59`
- `PsalmCommand.php:48`

## Fix
Replace `!$process->getExitCode() > 1` with `$process->getExitCode() <= 1` or
add parentheses: `!($process->getExitCode() > 1)`. The intent is to check if the
exit code is NOT greater than 1 (i.e., success = 0 or warning = 1).

Use `$process->getExitCode() <= 1` for clarity.

## Acceptance Criteria
- [ ] All 4 command files fixed
- [ ] Existing tests still pass
- [ ] New tests verify correct exit code handling

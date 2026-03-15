# Task 009: Fix command name mismatch in adapter config

## Module
Adapter/Yiisoft (`libs/Adapter/Yiisoft/config/params.php`)

## Issue (from module review)

### Bug: ignoredCommands pattern doesn't match (P0)
- `ignoredCommands` contains `'debug/reset'` (with slash)
- But `DebugResetCommand::COMMAND_NAME` is `'debug:reset'` (with colon)
- The reset command is never actually ignored

## Fix
Change `'debug/reset'` to `'debug:reset'` in the `ignoredCommands` array.
Also check for any other command name mismatches in the config.

## Acceptance Criteria
- [ ] Command name uses colon separator matching the actual command name constant
- [ ] Existing tests still pass

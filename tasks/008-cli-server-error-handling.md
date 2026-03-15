# Task 008: Add error handling to CLI server commands

## Module
CLI (`libs/Cli/src/Command/`)

## Issues (from module review)

### 1. No error handling for socket operations (P0)
`Connection::create()` and `$socket->bind()` have no try-catch in `DebugServerCommand.php`.
Port already in use or permission denied crashes with unhandled exception.

### 2. Unhandled JsonException (P1)
`json_decode()` with `JSON_THROW_ON_ERROR` in `DebugServerCommand.php:74` has no
try-catch — malformed JSON crashes the server loop.

### 3. DebugServerBroadcastCommand logic flaw (P0)
Creates socket, registers signal handler, immediately broadcasts and returns.
Signal handler never gets a chance to run. Socket never explicitly closed on success path.

## Fix
- Wrap socket operations in try-catch with user-friendly error messages
- Wrap json_decode in try-catch, log and skip malformed messages
- Fix broadcast command lifecycle — close socket after broadcast on success path

## Acceptance Criteria
- [ ] Socket errors produce friendly error messages instead of unhandled exceptions
- [ ] Malformed JSON doesn't crash the server loop
- [ ] Broadcast command properly closes socket on all paths
- [ ] Existing tests still pass

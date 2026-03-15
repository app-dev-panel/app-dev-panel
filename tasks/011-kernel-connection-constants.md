# Task 011: Replace magic error codes with named constants in Connection

## Module
Kernel (`libs/Kernel/src/DebugServer/Connection.php`)

## Issue (from module review)

### Magic numbers (P3)
- Error code `35` and `61` are hardcoded without explanation
- These are EAGAIN (35) and ECONNREFUSED (61)
- Also remove commented-out code

## Fix
Define named constants:
```php
private const SOCKET_EAGAIN = 35;
private const SOCKET_ECONNREFUSED = 61;
```
Replace hardcoded numbers with the constants.
Remove any commented-out dead code.

## Acceptance Criteria
- [ ] Magic numbers replaced with named constants
- [ ] Commented-out code removed
- [ ] Existing tests still pass

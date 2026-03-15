# Task 007: Replace empty catch blocks with logging

## Module
API (`libs/API/src/Inspector/Controller/InspectController.php`)

## Issue (from module review)

### Silent exception swallowing (P1)
`catch (Throwable) {}` with empty body silently hides all errors during translation loading.
Makes debugging impossible.

## Fix
Add logging to catch blocks. Inject `LoggerInterface` if not already present.
At minimum, log the exception message at warning/error level.

## Acceptance Criteria
- [ ] No empty catch blocks remain in InspectController
- [ ] Exceptions are logged with appropriate level
- [ ] Existing tests still pass

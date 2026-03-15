# Task 002: Optimize debug_backtrace() in proxy classes

## Module
Kernel (`libs/Kernel/src/Collector/`)

## Issues (from module review)

### debug_backtrace() called without limits (P1)
Affects:
- `LoggerInterfaceProxy.php`
- `EventDispatcherInterfaceProxy.php`
- `HttpClientInterfaceProxy.php`

`debug_backtrace()` is expensive — called on every log, event dispatch, and HTTP request.
No `DEBUG_BACKTRACE_IGNORE_ARGS` flag and no `limit` parameter.

## Fix
Replace all `debug_backtrace()` calls with:
```php
debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)
```
Only 1 frame is needed (the caller). `IGNORE_ARGS` avoids copying argument data.

## Acceptance Criteria
- [ ] All `debug_backtrace()` calls use `DEBUG_BACKTRACE_IGNORE_ARGS` and limit `1`
- [ ] Existing tests still pass
- [ ] Verify the backtrace data used downstream still works with the limited trace

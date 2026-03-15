# Task 003: Refactor LoggerInterfaceProxy to eliminate duplication

## Module
Kernel (`libs/Kernel/src/Collector/LoggerInterfaceProxy.php`)

## Issue (from module review)

### Massive code duplication (P1)
All 8 log level methods (emergency, alert, critical, error, warning, notice, info, debug)
are copy-pasted with identical logic. Should delegate to `log()` method.

## Fix
Keep the `log()` method with the collector logic. Make all 8 level methods delegate to `log()`:
```php
public function emergency(string|\Stringable $message, array $context = []): void
{
    $this->log(LogLevel::EMERGENCY, $message, $context);
}
```

## Acceptance Criteria
- [ ] All 8 level methods delegate to `log()`
- [ ] No duplicated collector/backtrace logic in individual methods
- [ ] Existing tests still pass

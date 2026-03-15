# Task 010: Extract duplicated enabled-check to shared helper

## Module
Adapter/Yiisoft (`libs/Adapter/Yiisoft/config/`)

## Issue (from module review)

### Code duplication (P1)
`if (!(bool)($params['app-dev-panel/yii-debug']['enabled'] ?? false))` is
copy-pasted in `di.php`, `di-web.php`, `di-console.php`.

## Fix
Extract to a shared helper function or a small config utility class.
Example:
```php
// In a shared location
function isDebugEnabled(array $params): bool
{
    return (bool)($params['app-dev-panel/yii-debug']['enabled'] ?? false);
}
```
Then replace all 3 occurrences.

## Acceptance Criteria
- [ ] Enabled check extracted to single location
- [ ] All 3 config files use the shared helper
- [ ] Existing tests still pass

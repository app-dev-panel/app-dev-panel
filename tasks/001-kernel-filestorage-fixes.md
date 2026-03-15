# Task 001: Fix FileStorage bugs

## Module
Kernel (`libs/Kernel/src/Storage/FileStorage.php`)

## Issues (from module review)

### 1. Variable shadowing in `read()` (P0)
- `$id` parameter is overwritten inside the loop: `$id = substr($dir, ...)`
- The `?string $id` parameter loses its original value after the first iteration
- **Fix**: Use a different variable name inside the loop (e.g. `$entryId`)

### 2. No error handling in `flush()` for `file_put_contents` (P1)
- `file_put_contents()` can return `false` on failure, but the result is never checked
- Silent data loss if disk is full or permissions are wrong
- **Fix**: Check the return value and throw a `RuntimeException` on failure

## Acceptance Criteria
- [ ] Variable shadowing fixed — original `$id` preserved throughout `read()`
- [ ] `file_put_contents` return value checked, exception thrown on failure
- [ ] Existing tests still pass
- [ ] New tests cover both fixes

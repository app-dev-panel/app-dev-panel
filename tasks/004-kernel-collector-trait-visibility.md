# Task 004: Make CollectorTrait::reset() protected

## Module
Kernel (`libs/Kernel/src/Collector/CollectorTrait.php`)

## Issue (from module review)

### Private empty method cannot be overridden (P2)
`CollectorTrait::reset()` is `private` — classes using the trait cannot override it
with custom reset logic.

## Fix
Change `private function reset(): void` to `protected function reset(): void`

## Acceptance Criteria
- [ ] `reset()` visibility changed to `protected`
- [ ] Existing tests still pass

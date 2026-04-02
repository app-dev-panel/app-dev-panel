---
title: Validator Collector
description: "ADP Validator Collector records validation runs with rules, data, and error messages for debugging."
---

# Validator Collector

Captures validation operations with rules, results, and error lists.

![Validator Collector panel](/images/collectors/validator.png)

## What It Captures

| Field | Description |
|-------|-------------|
| `value` | The validated value |
| `rules` | Validation rules applied |
| `result` | Whether validation passed (`true`/`false`) |
| `errors` | Validation error messages |

## Data Schema

```json
[
    {
        "value": {"email": "invalid"},
        "rules": "email|required",
        "result": false,
        "errors": ["The email field must be a valid email address."]
    },
    {
        "value": {"name": "John"},
        "rules": "string|min:2",
        "result": true,
        "errors": []
    }
]
```

**Summary** (shown in debug entry list):

```json
{
    "validator": {
        "total": 2,
        "valid": 1,
        "invalid": 1
    }
}
```

## Contract

```php
use AppDevPanel\Kernel\Collector\ValidatorCollector;

$collector->collect(
    value: ['email' => 'invalid'],
    isValid: false,
    errors: ['The email field must be a valid email address.'],
    rules: 'email|required',
);
```

::: info
<class>\AppDevPanel\Kernel\Collector\ValidatorCollector</class> implements <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. It has no dependencies on other collectors.
:::

## How It Works

Framework adapters intercept validation calls:
- **Symfony**: Validator event listener
- **Laravel**: Validator hook after validation
- **Yii 3**: Validator proxy decorator

## Debug Panel

- **Validation list** — all validations with pass/fail status
- **Status badges** — valid (green), invalid (red)
- **Error details** — expandable error messages per validation
- **Rule display** — validation rules shown for each operation

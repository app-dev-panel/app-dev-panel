---
title: Коллектор валидации
---

# Коллектор валидации

Захватывает операции валидации с правилами, результатами и списками ошибок.

![Панель коллектора валидации](/images/collectors/validator.png)

## Собираемые данные

| Поле | Описание |
|------|----------|
| `value` | Валидируемое значение |
| `rules` | Применённые правила валидации |
| `result` | Прошла ли валидация (`true`/`false`) |
| `errors` | Сообщения об ошибках валидации |

## Схема данных

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

**Сводка** (отображается в списке отладочных записей):

```json
{
    "validator": {
        "total": 2,
        "valid": 1,
        "invalid": 1
    }
}
```

## Контракт

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
<class>\AppDevPanel\Kernel\Collector\ValidatorCollector</class> реализует <class>\AppDevPanel\Kernel\Collector\SummaryCollectorInterface</class>. Не имеет зависимостей от других коллекторов.
:::

## Как это работает

Адаптеры фреймворков перехватывают вызовы валидации:
- **Symfony**: слушатель событий валидатора
- **Laravel**: хук валидатора после проверки
- **Yii 3**: прокси-декоратор валидатора

## Панель отладки

- **Список валидаций** — все валидации со статусом прохождения/ошибки
- **Бейджи статусов** — пройдено (зелёный), ошибка (красный)
- **Детали ошибок** — раскрываемые сообщения об ошибках для каждой валидации
- **Отображение правил** — правила валидации для каждой операции

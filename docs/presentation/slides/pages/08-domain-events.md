# Доменные события — бесплатный контекст

| Событие | Что получаем |
|---|---|
| `UserLoggedIn` | id, роли, guard |
| `RouteMatched` | controller, action |
| `CacheMiss` | key, driver, duration |
| `QueryExecuted` | SQL, bindings, duration |

<!--
Speaker notes:
Обычные листенеры логируют факт события. Но если мы
знаем конкретный тип события — можно выжать из него
структурированный контекст.

На UserLoggedIn уже есть id, роли, guard. На RouteMatched
известны controller и action. На CacheMiss — ключ,
драйвер, длительность. На QueryExecuted — SQL, байндинги,
время выполнения.

То есть из «что-то произошло» мы получаем «кто, где,
зачем». Это то, за что любят Telescope в Laravel — и
то же самое получается у нас бесплатно, из общей
архитектуры.
-->

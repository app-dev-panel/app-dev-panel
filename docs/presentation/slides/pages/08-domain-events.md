# События фреймворка — бесплатный контекст

| Событие | Что получаем |
|---|---|
| `UserLoggedIn` | id, роли, guard |
| `RouteMatched` | controller, action |
| `CacheMiss` | key, driver, duration |
| `QueryExecuted` | SQL, bindings, duration |

<!--
Speaker notes:
Это не доменные события — это события фреймворка.
Они уже есть в Symfony, Laravel, Yii — нам их генерировать
не надо.

Обычный листенер просто логирует факт. Но если мы знаем
конкретный тип — выжимаем структурированный контекст.

На UserLoggedIn уже есть id, роли, guard. На RouteMatched
известны controller и action. На CacheMiss — ключ,
драйвер, длительность. На QueryExecuted — SQL, байндинги,
время выполнения.

Из «что-то произошло» получаем «кто, где, зачем». Это
то, за что любят Telescope в Laravel — и то же самое
получается у нас бесплатно, из событий фреймворка.
-->

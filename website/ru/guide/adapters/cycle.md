# Адаптер Cycle ORM

Адаптер Cycle ORM — облегченный адаптер, предоставляющий только инспекцию схемы базы данных. В отличие от полных адаптеров, он не содержит коллекторов, слушателей событий и управления жизненным циклом.

## Установка

```bash
composer require app-dev-panel/adapter-cycle
```

## Использование

Зарегистрируйте <class>AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider</class> как <class>AppDevPanel\Api\Inspector\Database\SchemaProviderInterface</class> в DI-контейнере вашего фреймворка:

```php
use AppDevPanel\Adapter\Cycle\Inspector\CycleSchemaProvider;
use AppDevPanel\Api\Inspector\Database\SchemaProviderInterface;
use Cycle\Database\DatabaseProviderInterface;

SchemaProviderInterface::class => static fn (DatabaseProviderInterface $db) => new CycleSchemaProvider($db),
```

## Возможности

| Метод | Статус | Описание |
|-------|--------|----------|
| `getTables()` | Реализован | Список всех таблиц с колонками, первичными ключами, количеством записей |
| `getTable()` | Реализован | Схема таблицы с пагинацией записей |
| `explainQuery()` | Заглушка | Возвращает пустой массив |
| `executeQuery()` | Заглушка | Возвращает пустой массив |

## Когда использовать

Используйте адаптер Cycle, когда ваше приложение использует Cycle ORM и вы хотите инспектировать схему БД в панели ADP. Комбинируйте его с полным адаптером фреймворка (Yii 3, Symfony, Laravel), который управляет жизненным циклом, коллекторами и API.

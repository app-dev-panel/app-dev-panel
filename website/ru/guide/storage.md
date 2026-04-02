---
title: Хранилище
description: "Как ADP сохраняет отладочные данные через StorageInterface. FileStorage по умолчанию пишет JSON-файлы на диск."
---

# Хранилище

ADP сохраняет отладочные данные через абстракцию <class>AppDevPanel\Kernel\Storage\StorageInterface</class>. Реализация по умолчанию -- <class>AppDevPanel\Kernel\Storage\FileStorage</class> -- записывает JSON-файлы на диск.

## StorageInterface

```php
interface StorageInterface
{
    public function addCollector(CollectorInterface $collector): void;
    public function getData(): array;
    public function read(string $type, ?string $id = null): array;
    public function write(string $id, array $summary, array $data, array $objects): void;
    public function flush(): void;
    public function clear(): void;
}
```

### Типы данных

Каждая отладочная запись хранится в виде трёх отдельных частей:

| Тип | Константа | Содержимое |
|-----|-----------|------------|
| Сводка | `TYPE_SUMMARY` | Время, URL, HTTP-статус, имена коллекторов |
| Данные | `TYPE_DATA` | Полные данные коллекторов |
| Объекты | `TYPE_OBJECTS` | Сериализованные PHP-объекты для детальной инспекции |

Такое разделение позволяет фронтенду быстро загружать сводки без получения полных данных.

## FileStorage

<class>AppDevPanel\Kernel\Storage\FileStorage</class> -- реализация по умолчанию для продакшена. Записывает JSON-файлы в настраиваемую директорию.

**Основные особенности:**

- Каждая запись создаёт три JSON-файла (summary, data, objects)
- Использует `LOCK_EX` для атомарной записи
- Использует `flock` для взаимного исключения при сборке мусора
- Поддерживает настраиваемый лимит записей с автоматической очисткой старых

### Структура директории

```
debug-data/
├── 000001.summary.json
├── 000001.data.json
├── 000001.objects.json
├── 000002.summary.json
├── 000002.data.json
├── 000002.objects.json
└── ...
```

## MemoryStorage

<class>AppDevPanel\Kernel\Storage\MemoryStorage</class> -- реализация в памяти, используемая исключительно для тестирования. Хранит все данные в массивах PHP без дискового ввода-вывода.

## Источники записи

Хранилище получает данные из двух источников:

1. **Сброс Debugger** -- после завершения запроса или консольной команды <class>AppDevPanel\Kernel\Debugger</class> вызывает `flush()`, который сериализует все данные коллекторов.
2. **Ingestion API** -- <class>AppDevPanel\Api\Ingestion\Controller\IngestionController</class> вызывает `write()` напрямую, позволяя внешним (не-PHP) приложениям отправлять отладочные данные через HTTP.

## Расширение хранилища

Для создания собственного бэкенда (например, Redis, база данных) реализуйте <class>AppDevPanel\Kernel\Storage\StorageInterface</class> и зарегистрируйте его в DI-контейнере. Метод `read()` должен поддерживать фильтрацию по `$type` и опционально по `$id`.
